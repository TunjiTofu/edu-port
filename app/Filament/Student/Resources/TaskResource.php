<?php

namespace App\Filament\Student\Resources;

use App\Enums\SubmissionTypes;
use App\Filament\Student\Resources\TaskResource\Pages;
use App\Filament\Student\Widgets\UpcomingDeadlinesWidget;
use App\Models\Submission;
use App\Models\Task;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Wizard;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TaskResource extends Resource
{
    protected static ?string $model           = Task::class;
    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'My Tasks';
    protected static ?string $navigationGroup = 'Submissions';
    protected static ?int    $navigationSort  = 1;

    public static function canViewAny(): bool       { return Auth::user()?->isStudent(); }
    public static function canCreate(): bool        { return false; }
    public static function canEdit($record): bool   { return false; }
    public static function canDelete($record): bool { return false; }

    // ── Query ──────────────────────────────────────────────────────────────

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('is_active', true)
            ->whereHas('section.trainingProgram.enrollments', fn ($q) =>
            $q->where('student_id', Auth::id())
            )
            ->with([
                'section.trainingProgram',
                'submissions' => fn ($q) => $q->where('student_id', Auth::id())->with('review'),
            ])
            ->orderBy('due_date');
    }

    // ── Navigation Badge — count of unsubmitted tasks ──────────────────────

    public static function getNavigationBadge(): ?string
    {
        $pending = Task::where('is_active', true)
            ->whereHas('section.trainingProgram.enrollments', fn ($q) =>
            $q->where('student_id', Auth::id())
            )
            ->whereDoesntHave('submissions', fn ($q) =>
            $q->where('student_id', Auth::id())
            )
            ->where(fn ($q) => $q->where('due_date', '>=', now())->orWhereNull('due_date'))
            ->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string { return 'warning'; }

    // ── Table ──────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([

                        // Status emoji pill
                        Tables\Columns\TextColumn::make('status_icon')
                            ->label('')
                            ->getStateUsing(function ($record) {
                                $sub = $record->submissions->first();
                                if (! $sub) return $record->due_date?->isPast() ? '🔴' : '📝';
                                return match ($sub->status) {
                                    SubmissionTypes::COMPLETED->value      => '✅',
                                    SubmissionTypes::PENDING_REVIEW->value => '⏳',
                                    SubmissionTypes::UNDER_REVIEW->value   => '🔍',
                                    SubmissionTypes::NEEDS_REVISION->value => '⚠️',
                                    SubmissionTypes::FLAGGED->value        => '🚩',
                                    default                                => '📤',
                                };
                            })
                            ->grow(false)
                            ->extraAttributes(['class' => 'text-2xl leading-none pt-1']),

                        Tables\Columns\Layout\Stack::make([
                            Tables\Columns\TextColumn::make('title')
                                ->label('')->weight('bold')
                                ->size(Tables\Columns\TextColumn\TextColumnSize::Medium)
                                ->searchable(),

                            Tables\Columns\TextColumn::make('section.name')
                                ->label('')
                                ->formatStateUsing(fn ($state, $record) =>
                                    ($record->section?->trainingProgram?->name ?? '') . ' › ' . $state
                                )
                                ->color('gray')
                                ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall),

                            Tables\Columns\Layout\Grid::make(['default' => 1, 'sm' => 2])
                                ->schema([
                                    Tables\Columns\TextColumn::make('due_date')
                                        ->badge()
                                        ->color(function ($state, $record) {
                                            if (! $state) return 'gray';
                                            if ($record->submissions->isNotEmpty()) return 'success';
                                            $days = now()->diffInDays($state, false);
                                            return match (true) {
                                                $days < 0  => 'danger',
                                                $days <= 3 => 'warning',
                                                default    => 'info',
                                            };
                                        })
                                        ->formatStateUsing(function ($state, $record) {
                                            if (! $state) return '🗓 No deadline';
                                            $c    = $state instanceof \Carbon\Carbon ? $state : \Carbon\Carbon::parse($state);
                                            $days = round(now()->diffInDays($c, false));
                                            if ($record->submissions->isNotEmpty()) return '📤 Submitted';
                                            return match (true) {
                                                $days < 0  => '🔴 Overdue — ' . $c->format('M j'),
                                                $days == 0 => '⚠️ Due today',
                                                $days <= 3 => "⚠️ {$days}days left",
                                                default    => '📅 ' . $c->format('M j, Y'),
                                            };
                                        }),

                                    Tables\Columns\TextColumn::make('submission_status')
                                        ->badge()
                                        ->getStateUsing(fn ($record) =>
                                            $record->submissions->first()?->status ?? 'not_submitted'
                                        )
                                        ->color(fn ($state) => match ($state) {
                                            SubmissionTypes::COMPLETED->value      => 'success',
                                            SubmissionTypes::PENDING_REVIEW->value => 'info',
                                            SubmissionTypes::UNDER_REVIEW->value   => 'warning',
                                            SubmissionTypes::NEEDS_REVISION->value => 'danger',
                                            SubmissionTypes::FLAGGED->value        => 'danger',
                                            'not_submitted'                         => 'gray',
                                            default                                 => 'gray',
                                        })
                                        ->formatStateUsing(fn ($state) => match ($state) {
                                            SubmissionTypes::COMPLETED->value      => 'Completed',
                                            SubmissionTypes::PENDING_REVIEW->value => 'Awaiting Review',
                                            SubmissionTypes::UNDER_REVIEW->value   => 'Under Review',
                                            SubmissionTypes::NEEDS_REVISION->value => 'Needs Revision',
                                            SubmissionTypes::FLAGGED->value        => 'Flagged',
                                            'not_submitted'                         => 'Not Submitted',
                                            default                                 => ucfirst(str_replace('_', ' ', $state)),
                                        }),
                                ]),
                        ])->space(1)->grow(true),
                    ])->from('sm'),
                ])->space(2),
            ])
            ->contentGrid(['default' => 1, 'md' => 2])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'not_submitted'                        => 'Not Submitted',
                        SubmissionTypes::PENDING_REVIEW->value => 'Awaiting Review',
                        SubmissionTypes::UNDER_REVIEW->value   => 'Under Review',
                        SubmissionTypes::NEEDS_REVISION->value => 'Needs Revision',
                        SubmissionTypes::COMPLETED->value      => 'Completed',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) return $query;
                        if ($data['value'] === 'not_submitted') {
                            return $query->whereDoesntHave('submissions',
                                fn ($q) => $q->where('student_id', Auth::id())
                            );
                        }
                        return $query->whereHas('submissions',
                            fn ($q) => $q->where('student_id', Auth::id())->where('status', $data['value'])
                        );
                    }),

                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue')
                    ->toggle()
                    ->query(fn (Builder $query) =>
                    $query->whereNotNull('due_date')
                        ->where('due_date', '<', now())
                        ->whereDoesntHave('submissions', fn ($q) =>
                        $q->where('student_id', Auth::id())
                        )
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->button()->color('gray'),

                // First submission
                Tables\Actions\Action::make('submit')
                    ->label('Submit')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->button()->color('success')
                    ->visible(fn ($record) => $record->submissions->isEmpty())
                    ->form(fn () => static::submissionWizard())
                    ->action(fn ($record, $data) => static::handleSubmission($record, $data)),

                // Resubmit — only while pending_review (not yet picked up by a reviewer)
                Tables\Actions\Action::make('resubmit')
                    ->label('Resubmit')
                    ->icon('heroicon-o-arrow-path')
                    ->button()->color('warning')
                    ->visible(fn ($record) =>
                        $record->submissions->first()?->status === SubmissionTypes::PENDING_REVIEW->value
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Replace Your Submission?')
                    ->modalDescription('Resubmitting will replace your current file. Your previous submission will be deleted. You can only do this while the task is still awaiting review.')
                    ->modalSubmitActionLabel('Yes, Replace Submission')
                    ->form(fn () => static::submissionWizard())
                    ->action(fn ($record, $data) => static::handleResubmission($record, $data)),
            ])
            ->defaultSort('due_date')
            ->emptyStateHeading('No Tasks Yet')
            ->emptyStateDescription("Enroll in a training program to access tasks.")
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }

    // ── Infolist ───────────────────────────────────────────────────────────

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make()
                    ->schema([
                        Infolists\Components\Grid::make(['default' => 1, 'sm' => 3])
                            ->schema([
                                Infolists\Components\TextEntry::make('section.trainingProgram.name')
                                    ->label('Program')->badge()->color('info'),

                                Infolists\Components\TextEntry::make('section.name')
                                    ->label('Section')->badge()->color('gray'),

                                Infolists\Components\TextEntry::make('due_date')
                                    ->label('Due Date')
                                    ->badge()
                                    ->color(fn ($state) => $state?->isPast() ? 'danger' : 'success')
                                    ->formatStateUsing(fn ($state) => $state
                                        ? $state->format('M j, Y')
                                        : 'No deadline'
                                    ),
                            ]),

                        Infolists\Components\TextEntry::make('title')
                            ->label('')->size('lg')->weight('bold')->columnSpanFull(),

                        Infolists\Components\TextEntry::make('description')
                            ->label('Task Description')->html()->prose()->columnSpanFull()
                            ->visible(fn ($record) => ! empty($record->description)),

                        Infolists\Components\TextEntry::make('instructions')
                            ->label('Instructions')->html()->prose()->columnSpanFull()
                            ->visible(fn ($record) => ! empty($record->instructions)),
                    ]),

                // Rubrics
                Infolists\Components\Section::make('Evaluation Criteria')
                    ->icon('heroicon-o-star')
                    ->description('Your submission will be scored against these criteria.')
                    ->collapsible()
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('activeRubrics')
                            ->label('')
                            ->schema([
                                Infolists\Components\Grid::make(['default' => 1, 'sm' => 3])
                                    ->schema([
                                        Infolists\Components\TextEntry::make('name')->label('Criterion')->weight('medium'),
                                        Infolists\Components\TextEntry::make('max_points')->label('Max Points')->badge()->color('info'),
                                        Infolists\Components\TextEntry::make('description')->label('Description')->color('gray'),
                                    ]),
                            ])
                            ->contained(false),
                    ])
                    ->visible(fn ($record) => $record->activeRubrics->isNotEmpty()),

                // Submission panel
                Infolists\Components\Section::make('Your Submission')
                    ->icon('heroicon-o-document-arrow-up')
                    ->schema([
                        Infolists\Components\TextEntry::make('not_submitted')
                            ->label('')->state('You have not submitted this task yet.')
                            ->icon('heroicon-o-exclamation-circle')->color('warning')
                            ->visible(fn ($record) =>
                            $record->submissions->where('student_id', Auth::id())->isEmpty()
                            ),

                        Infolists\Components\Group::make([
                            Infolists\Components\Grid::make(['default' => 2, 'sm' => 4])
                                ->schema([
                                    Infolists\Components\TextEntry::make('submissions.0.status')
                                        ->label('Status')->badge()
                                        ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucfirst($state ?? ''))),

                                    Infolists\Components\TextEntry::make('submissions.0.submitted_at')
                                        ->label('Submitted')->since(),

                                    Infolists\Components\TextEntry::make('submissions.0.file_name')
                                        ->label('File'),

                                    Infolists\Components\TextEntry::make('submissions.0.file_size')
                                        ->label('Size')
                                        ->formatStateUsing(fn ($state) =>
                                        $state ? number_format($state / 1024, 1) . ' KB' : '—'
                                        ),
                                ]),

                            Infolists\Components\TextEntry::make('submissions.0.student_notes')
                                ->label('Your Notes')->prose()->columnSpanFull()
                                ->visible(fn ($record) => ! empty($record->submissions->first()?->student_notes)),

                            Infolists\Components\TextEntry::make('submissions.0.review.score')
                                ->label('Score')->badge()->color('success')
                                ->formatStateUsing(fn ($state, $record) =>
                                $state !== null ? $state . ' / ' . $record->max_score : 'Not yet graded'
                                )
                                ->visible(fn ($record) => $record->isResultPublished()),

                            Infolists\Components\TextEntry::make('submissions.0.review.comments')
                                ->label('Reviewer Comments')->prose()->columnSpanFull()
                                ->visible(fn ($record) =>
                                    ! empty($record->submissions->first()?->review?->comments) &&
                                    $record->isResultPublished()
                                ),

                            // Resubmit notice
                            Infolists\Components\TextEntry::make('resubmit_note')
                                ->label('')
                                ->state('⚠️ Your submission is awaiting review. You may replace it until a reviewer picks it up.')
                                ->color('warning')
                                ->visible(fn ($record) =>
                                    $record->submissions->first()?->status === SubmissionTypes::PENDING_REVIEW->value
                                ),
                        ])->visible(fn ($record) =>
                        $record->submissions->where('student_id', Auth::id())->isNotEmpty()
                        ),
                    ]),
            ]);
    }

    // ── Submission Wizard ──────────────────────────────────────────────────

    public static function submissionWizard(): array
    {
        return [
            Wizard::make([
                Wizard\Step::make('Upload')
                    ->icon('heroicon-o-document-arrow-up')
                    ->description('Choose your file')
                    ->schema([
                        FileUpload::make('file')
                            ->label('Upload Your Work')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            ])
                            ->maxSize(10240)
                            ->required()
                            ->directory('submissions/temp')
                            ->preserveFilenames()
                            ->disk('public')
                            ->storeFileNamesIn('original_file_name')
                            ->helperText('PDF, DOC or DOCX — max 2 MB'),

                        Textarea::make('notes')
                            ->label('Notes for your Reviewer (Optional)')
                            ->rows(3)
                            ->placeholder('Any context or comments about your work...'),
                    ]),

                Wizard\Step::make('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->description('Review before submitting')
                    ->schema([
                        Placeholder::make('file_preview')
                            ->label('File selected')
                            ->content(fn ($get) => $get('original_file_name') ?: '—'),

                        Placeholder::make('notes_preview')
                            ->label('Your notes')
                            ->content(fn ($get) => $get('notes') ?: 'None'),

                        Checkbox::make('confirm_submission')
                            ->label('I confirm this is my own original work and I am ready to submit.')
                            ->required()
                            ->accepted(),
                    ]),
            ]),
        ];
    }

    // ── Handlers ───────────────────────────────────────────────────────────

    public static function handleSubmission(Task $record, array $data): void
    {
        $context = [
            'candidate_id' => Auth::id(),
            'task_id'      => $record->id,
            'task_title'   => $record->title,
            'ip'           => request()->ip(),
        ];

        // ── Server-side guards — cannot be bypassed by UI manipulation ────
        // Check 1: task overdue
        if ($record->due_date && $record->due_date->isPast()) {
            Log::warning('Submission: blocked — task overdue', array_merge($context, [
                'event'    => 'submission_blocked_overdue',
                'due_date' => $record->due_date->toDateTimeString(),
            ]));
            Notification::make()
                ->title('Deadline Passed')
                ->body('The deadline for this task has passed. Submissions are no longer accepted.')
                ->danger()->send();
            return;
        }

        // Check 2: candidate graduated or disqualified
        $candidate = Auth::user();
        if ($candidate?->hasCompletedProgram() || $candidate?->isDisqualified()) {
            Log::warning('Submission: blocked — candidate locked', array_merge($context, [
                'event'        => 'submission_blocked_candidate_locked',
                'graduated'    => $candidate->hasCompletedProgram(),
                'disqualified' => $candidate->isDisqualified(),
            ]));
            Notification::make()
                ->title('Submission Not Allowed')
                ->body('Your account does not have permission to submit assignments.')
                ->danger()->send();
            return;
        }

        // Check 3: already submitted
        if (Submission::where('task_id', $record->id)->where('student_id', Auth::id())->exists()) {
            Log::warning('Submission: blocked — already submitted', array_merge($context, [
                'event' => 'submission_blocked_duplicate',
            ]));
            Notification::make()
                ->title('Already Submitted')
                ->body('You have already submitted this task. Use Resubmit to replace it while it awaits review.')
                ->warning()->send();
            return;
        }

        Log::info('Submission: attempt', array_merge($context, ['event' => 'submission_attempt']));

        try {
            $fileDetails = UpcomingDeadlinesWidget::processSubmissionFile($data, $record);

            $submission = Submission::create([
                'task_id'       => $record->id,
                'student_id'    => Auth::id(),
                'file_name'     => $fileDetails['file_name'],
                'file_path'     => $fileDetails['file_path'],
                'file_size'     => $fileDetails['file_size'],
                'file_type'     => $fileDetails['file_type'],
                'student_notes' => $data['notes'] ?? null,
                'submitted_at'  => now(),
                'status'        => SubmissionTypes::PENDING_REVIEW->value,
            ]);

            Log::info('Submission: success', array_merge($context, [
                'event'         => 'submission_success',
                'submission_id' => $submission->id,
            ]));

            Notification::make()
                ->title('Submitted!')
                ->body('Your assignment has been submitted. You will be notified when it is reviewed.')
                ->success()->send();

        } catch (\Exception $e) {
            Log::error('Submission: error', array_merge($context, [
                'event' => 'submission_error', 'error' => $e->getMessage(),
            ]));
            Notification::make()->title('Submission Failed')->body($e->getMessage())->danger()->send();
        }
    }

    /**
     * Replace an existing PENDING_REVIEW submission with a new file.
     * The old file is deleted from storage and the submission record is updated.
     * This is blocked at the UI level once the status changes from pending_review.
     */
    public static function handleResubmission(Task $record, array $data): void
    {
        // ── Server-side guards ─────────────────────────────────────────────
        // Even though the UI hides the resubmit button for overdue tasks,
        // validate server-side so a stale page load cannot bypass the check.
        if ($record->due_date && $record->due_date->isPast()) {
            Log::warning('Resubmission: blocked — task overdue', [
                'event'        => 'resubmission_blocked_overdue',
                'candidate_id' => Auth::id(),
                'task_id'      => $record->id,
                'due_date'     => $record->due_date->toDateTimeString(),
            ]);
            Notification::make()
                ->title('Deadline Passed')
                ->body('The deadline for this task has passed. Resubmission is no longer accepted.')
                ->danger()->send();
            return;
        }

        $candidate = Auth::user();
        if ($candidate?->hasCompletedProgram() || $candidate?->isDisqualified()) {
            Notification::make()
                ->title('Resubmission Not Allowed')
                ->body('Your account does not have permission to submit assignments.')
                ->danger()->send();
            return;
        }

        $existing = Submission::where('task_id', $record->id)
            ->where('student_id', Auth::id())
            ->where('status', SubmissionTypes::PENDING_REVIEW->value)
            ->first();

        if (! $existing) {
            Notification::make()
                ->title('Resubmission Not Allowed')
                ->body('This task can no longer be resubmitted — it may already be under review.')
                ->warning()->send();
            return;
        }

        $context = [
            'candidate_id'  => Auth::id(),
            'task_id'       => $record->id,
            'submission_id' => $existing->id,
            'ip'            => request()->ip(),
        ];

        Log::info('Resubmission: attempt', array_merge($context, ['event' => 'resubmission_attempt']));

        try {
            $fileDetails = UpcomingDeadlinesWidget::processSubmissionFile(
                $data, $record, isResubmit: true, existingSubmission: $existing
            );

            $existing->update([
                'file_name'     => $fileDetails['file_name'],
                'file_path'     => $fileDetails['file_path'],
                'file_size'     => $fileDetails['file_size'],
                'file_type'     => $fileDetails['file_type'],
                'student_notes' => $data['notes'] ?? $existing->student_notes,
                'submitted_at'  => now(),
                'status'        => SubmissionTypes::PENDING_REVIEW->value,
            ]);

            Log::info('Resubmission: success', array_merge($context, ['event' => 'resubmission_success']));

            Notification::make()
                ->title('Resubmission Successful')
                ->body('Your new file has replaced the previous submission.')
                ->success()->send();

        } catch (\Exception $e) {
            Log::error('Resubmission: error', array_merge($context, [
                'event' => 'resubmission_error', 'error' => $e->getMessage(),
            ]));
            Notification::make()->title('Resubmission Failed')->body($e->getMessage())->danger()->send();
        }
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'view'  => Pages\ViewTask::route('/{record}'),
        ];
    }
}
