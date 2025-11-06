<?php

namespace App\Filament\Resources\StudentSubmissionsResource\Pages;

use App\Enums\SubmissionTypes;
use App\Filament\Resources\StudentSubmissionsResource;
use App\Models\User;
use App\Models\Submission;
use App\Services\Utility\Constants;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ManageStudentSubmissions extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = StudentSubmissionsResource::class;

//    protected static string $view = 'filament.resources.student-submissions-resource.pages.manage-student-submissions';
    protected static string $view = 'filament.student.pages.manage-student-submissions';

    public User $record;

    protected static bool $shouldRegisterNavigation = false;

    public function mount(User $record): void
    {
        // Load the user with all relationships
        $this->record = $record->load([
            'submissions.task.section',
            'submissions.review.reviewer',
            'church',
            'district'
        ]);

        // Verify this is a student
//        if ($this->record->role_id !== Constants::STUDENT_ID) {
//            abort(404, 'Student not found');
//        }

    }

    public function getTitle(): string
    {
        return "Submissions: {$this->record->name}";
    }

    public function getHeading(): string
    {
        return "Manage Submissions for {$this->record->name}";
    }

    public function getSubheading(): string
    {
        $totalSubmissions = $this->record->submissions()->count();
        $completedSubmissions = $this->record->submissions()
            ->where('status', SubmissionTypes::COMPLETED->value)
            ->count();

        $totalScore = $this->record->submissions()
            ->whereHas('review', fn ($q) => $q->whereNotNull('score'))
            ->with('review')
            ->get()
            ->sum(fn ($submission) => $submission->review?->score ?? 0);

        $maxPossibleScore = $this->record->submissions()
            ->whereHas('review', fn ($q) => $q->whereNotNull('score'))
            ->with('task')
            ->get()
            ->sum(fn ($submission) => $submission->task?->max_score ?? 0);

        $scoreText = $maxPossibleScore > 0
            ? "{$totalScore}/{$maxPossibleScore}"
            : "No scores yet";

        return "Total: {$totalSubmissions} submissions | Completed: {$completedSubmissions} | Current Score: {$scoreText}";
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Submission::query()
                    ->where('student_id', $this->record->id)
                    ->with(['task.section', 'review.reviewer'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('task.title')
                    ->label('Task')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->wrap(),

                Tables\Columns\TextColumn::make('task.section.name')
                    ->label('Section')
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make('task.max_score')
                    ->label('Max Score')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        SubmissionTypes::PENDING_REVIEW->value => 'warning',
                        SubmissionTypes::UNDER_REVIEW->value => 'info',
                        SubmissionTypes::COMPLETED->value => 'success',
                        SubmissionTypes::NEEDS_REVISION->value => 'secondary',
                        SubmissionTypes::FLAGGED->value => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('review.reviewer.name')
                    ->label('Reviewer')
                    ->default('Not assigned')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('review.score')
                    ->label('Score')
                    ->alignCenter()
                    ->badge()
                    ->color(function ($record) {
                        $score = $record->review?->score;
                        $maxScore = $record->task?->max_score ?? 10;

                        if ($score === null) return 'gray';

                        $percentage = ($score / $maxScore) * 100;

                        return match (true) {
                            $percentage >= 75 => 'success',
                            $percentage >= 50 => 'warning',
                            default => 'danger',
                        };
                    })
                    ->formatStateUsing(function ($record) {
                        $score = $record->review?->score;
                        $maxScore = $record->task?->max_score ?? 10;

                        return $score !== null ? "{$score}/{$maxScore}" : 'Not graded';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->since()
                    ->sortable(),

                Tables\Columns\TextColumn::make('review.reviewed_at')
                    ->label('Reviewed')
                    ->dateTime()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        SubmissionTypes::PENDING_REVIEW->value => 'Pending Review',
                        SubmissionTypes::UNDER_REVIEW->value => 'Under Review',
                        SubmissionTypes::COMPLETED->value => 'Completed',
                        SubmissionTypes::NEEDS_REVISION->value => 'Needs Revision',
                        SubmissionTypes::FLAGGED->value => 'Flagged',
                    ]),

                Tables\Filters\SelectFilter::make('task')
                    ->relationship('task', 'title')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('graded')
                    ->label('Graded Only')
                    ->query(fn (Builder $query) => $query->whereHas('review', fn ($q) => $q->whereNotNull('score')))
                    ->toggle(),

                Tables\Filters\Filter::make('ungraded')
                    ->label('Ungraded Only')
                    ->query(fn (Builder $query) => $query->whereDoesntHave('review'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_content')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn (Submission $record) => "Submission: {$record->task->title}")
                    ->modalContent(fn (Submission $record) => view('filament.components.submission-content-modal', [
                        'submission' => $record,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->url(function (Submission $record) {
                        return $record->file_path ? static::getDownloadUrl($record) : null;
                    })
                    ->openUrlInNewTab()
                    ->visible(fn (Submission $record) => $record->file_path && $record->file_name),

                Tables\Actions\Action::make('score_submission')
                    ->label('Score')
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->form(function (Submission $record) {
                        $maxScore = $record->task->max_score ?? 10;

                        return [
                            Forms\Components\Section::make('Submission Details')
                                ->schema([
                                    Forms\Components\Placeholder::make('task_title')
                                        ->label('Task')
                                        ->content($record->task->title)
                                        ->columnSpanFull(),
//
//                                    Forms\Components\Placeholder::make('task_description')
//                                        ->label('Task Decription')
//                                        ->content($record->task->description)
//                                        ->columnSpanFull(),

                                    Forms\Components\View::make('filament.components.task-description')
                                        ->viewData([
                                            'description' => $record->task->description
                                        ])
                                        ->columnSpanFull(),

                                    Forms\Components\Placeholder::make('max_score_display')
                                        ->label('Maximum Score')
                                        ->content($maxScore),

                                    Forms\Components\Placeholder::make('submitted_date')
                                        ->label('Submitted At')
                                        ->content($record->submitted_at?->format('M d, Y H:i')),

                                    // ✅ your new download button here
                                    Forms\Components\Actions::make([
                                        Forms\Components\Actions\Action::make('download_submission')
                                            ->label('Download File')
                                            ->icon('heroicon-o-arrow-down-tray')
                                            ->color('primary')
                                            ->visible(fn (Submission $record) => $record->file_path && $record->file_name)
                                            ->url(fn (Submission $record) => static::getDownloadUrl($record))
                                            ->openUrlInNewTab()
                                    ])
                                        ->columnSpanFull(),
                                ])
                                ->columns(3),

                            Forms\Components\Section::make('Scoring')
                                ->schema([
                                    Forms\Components\TextInput::make('score')
                                        ->label('Score')
                                        ->numeric()
                                        ->required()
                                        ->minValue(0)
                                        ->maxValue($maxScore)
                                        ->default($record->review?->score)
                                        ->helperText("Maximum score for this task is {$maxScore}")
                                        ->suffix("/ {$maxScore}"),

                                    Forms\Components\Select::make('status')
                                        ->label('Status')
                                        ->options([
                                            SubmissionTypes::COMPLETED->value => 'Completed',
                                            SubmissionTypes::NEEDS_REVISION->value => 'Needs Revision',
                                            SubmissionTypes::FLAGGED->value => 'Flagged',
                                        ])
                                        ->required()
                                        ->default(SubmissionTypes::COMPLETED->value),

                                    Forms\Components\Textarea::make('comments')
                                        ->label('Comments')
                                        ->rows(4)
                                        ->default($record->review?->comments)
                                        ->helperText('Provide feedback for the student')
                                        ->columnSpanFull(),

                                    Forms\Components\Toggle::make('admin_override')
                                        ->label('Admin Override')
                                        ->helperText('Check if this is an administrative override of a previous review')
                                        ->reactive()
                                        ->default(false),

                                    Forms\Components\Textarea::make('override_reason')
                                        ->label('Override Reason')
                                        ->rows(2)
                                        ->required(fn (Forms\Get $get) => $get('admin_override'))
                                        ->visible(fn (Forms\Get $get) => $get('admin_override'))
                                        ->helperText('Explain why this override is necessary')
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),
                        ];
                    })
                    ->action(function (Submission $record, array $data) {
                        // Update or create the review
                        $reviewData = [
                            'score' => $data['score'],
                            'comments' => $data['comments'] ?? null,
                            'is_completed' => true,
                            'reviewed_at' => now(),
                        ];

                        // Handle admin override
                        if ($data['admin_override'] ?? false) {
                            $reviewData['admin_override'] = true;
                            $reviewData['override_reason'] = $data['override_reason'];
                            $reviewData['overridden_by'] = Auth::id();
                            $reviewData['overridden_at'] = now();
                        }

                        // If no reviewer assigned, set current admin as reviewer
                        if (!$record->review?->reviewer_id) {
                            $reviewData['reviewer_id'] = Auth::id();
                        }

                        $record->review()->updateOrCreate(
                            ['submission_id' => $record->id],
                            $reviewData
                        );

                        // Update submission status
                        $record->update([
                            'status' => $data['status'],
                        ]);

                        Notification::make()
                            ->title('Submission scored successfully')
                            ->success()
                            ->send();
                    })
                    ->modalHeading('Score Submission')
                    ->modalSubmitActionLabel('Save Score')
                    ->modalWidth('2xl'),

                Tables\Actions\ViewAction::make()
                    ->url(fn (Submission $record): string =>
                    route('filament.admin.resources.submissions.view', ['record' => $record])
                    ),
            ])
            ->headerActions([
                Tables\Actions\Action::make('back')
                    ->label('Back to Students')
                    ->icon('heroicon-o-arrow-left')
                    ->color('gray')
                    ->url(StudentSubmissionsResource::getUrl('index')),
            ])
            ->defaultSort('submitted_at', 'desc');
    }

    protected static function getDownloadUrl(Submission $submission): ?string
    {
        try {
            $fullPath = $submission->file_path . '/' . $submission->file_name;

            if (!Storage::disk(config('filesystems.default'))->exists($fullPath)) {
                Log::error("File not found at path: {$fullPath}");
                return null;
            }

            return Storage::disk(config('filesystems.default'))
                ->temporaryUrl(
                    $fullPath,
                    now()->addMinutes(30),
                    [
                        'ResponseContentDisposition' => 'attachment; filename="' . $submission->file_name . '"'
                    ]
                );
        } catch (\Exception $e) {
            Log::error("Failed to generate download URL: " . $e->getMessage());
            return null;
        }
    }
}
