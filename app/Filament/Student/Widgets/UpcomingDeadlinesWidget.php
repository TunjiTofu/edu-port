<?php

namespace App\Filament\Student\Widgets;

use App\Enums\SubmissionTypes;
use App\Models\Submission;
use App\Models\Task;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Wizard;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UpcomingDeadlinesWidget extends BaseWidget
{
    protected static ?int      $sort        = 3;
    protected int|string|array $columnSpan  = 'full';
    protected static ?string   $heading     = 'Upcoming Deadlines';
    protected static ?string   $description = 'Tasks that need your attention';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Task::query()
                    ->whereHas('section.trainingProgram.enrollments', function ($query) {
                        $query->where('student_id', auth()->id());
                    })
                    ->whereDoesntHave('submissions', function ($query) {
                        $query->where('student_id', auth()->id());
                    })
                    ->where(function ($query) {
                        $query->where('due_date', '>=', now())
                            ->orWhereNull('due_date');
                    })
                    ->with(['section.trainingProgram'])
                    ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('due_date', 'asc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('section.trainingProgram.name')
                    ->label('Program')
                    ->badge()
                    ->color('info')
                    ->limit(20),

                Tables\Columns\TextColumn::make('title')
                    ->label('Task')
                    ->weight('bold')
                    ->limit(35)
                    ->tooltip(fn ($record) => $record->title),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->formatStateUsing(function ($state) {
                        if (! $state) return 'No deadline';
                        $daysLeft  = now()->diffInDays($state, false);
                        $wholeDays = (int) round($daysLeft);
                        if ($daysLeft == 0) return 'Due today';
                        if ($daysLeft == 1) return 'Due tomorrow';
                        if ($daysLeft >  0) return "Due in {$wholeDays} days";
                        return 'Overdue';
                    })
                    ->badge()
                    ->color(function ($state) {
                        if (! $state) return 'gray';
                        $daysLeft = now()->diffInDays($state, false);
                        return match (true) {
                            $daysLeft < 0  => 'danger',
                            $daysLeft <= 1 => 'danger',
                            $daysLeft <= 3 => 'warning',
                            $daysLeft <= 7 => 'info',
                            default        => 'success',
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('submit')
                    ->label('Submit Now')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->form([
                        Wizard::make([
                            Wizard\Step::make('Upload File')
                                ->icon('heroicon-o-document-arrow-up')
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
                                        ->helperText('Accepted: PDF, DOC, DOCX — Max 10MB'),

                                    Textarea::make('notes')
                                        ->label('Notes (Optional)')
                                        ->rows(3)
                                        ->placeholder('Any notes or comments for your reviewer...'),
                                ]),

                            Wizard\Step::make('Confirm Submission')
                                ->icon('heroicon-o-check-circle')
                                ->schema([
                                    Placeholder::make('file_info')
                                        ->label('File to submit')
                                        ->content(fn ($get) => $get('original_file_name') ?: 'No file selected'),

                                    Placeholder::make('notes_info')
                                        ->label('Your notes')
                                        ->content(fn ($get) => $get('notes') ?: 'No notes provided'),

                                    Checkbox::make('confirm_submission')
                                        ->label('I confirm that this is my own work and I want to submit this assignment')
                                        ->required()
                                        ->accepted(),
                                ]),
                        ]),
                    ])
                    ->action(function ($record, $data) {
                        // ── Server-side guard — deadline check ─────────────
                        // The widget query already excludes submitted tasks,
                        // but the due_date check must also be validated here
                        // because a page can stay open past midnight and a
                        // user on a stale page could still trigger the action.
                        if ($record->due_date && $record->due_date->isPast()) {
                            Notification::make()
                                ->title('Deadline Passed')
                                ->body('The deadline for this task has passed. Submissions are no longer accepted.')
                                ->danger()->send();
                            return;
                        }

                        $candidate = Auth::user();
                        if ($candidate?->hasCompletedProgram() || $candidate?->isDisqualified()) {
                            Notification::make()
                                ->title('Submission Not Allowed')
                                ->body('Your account does not have permission to submit assignments.')
                                ->danger()->send();
                            return;
                        }

                        $context = [
                            'candidate_id'    => Auth::id(),
                            'candidate_email' => Auth::user()?->email,
                            'task_id'         => $record->id,
                            'task_title'      => $record->title,
                            'original_name'   => $data['original_file_name'] ?? 'unknown',
                            'ip'              => request()->ip(),
                        ];

                        Log::info('Submission: candidate initiated file submission', array_merge($context, [
                            'event' => 'submission_attempt',
                        ]));

                        try {
                            $fileDetails = static::processSubmissionFile($data, $record);

                            $submission = Submission::create([
                                'task_id'       => $record->id,
                                'student_id'    => Auth::id(),
                                'content_text'  => null,
                                'file_name'     => $fileDetails['file_name'],
                                'file_path'     => $fileDetails['file_path'],
                                'file_size'     => $fileDetails['file_size'],
                                'file_type'     => $fileDetails['file_type'],
                                'student_notes' => $data['notes'] ?? null,
                                'submitted_at'  => now(),
                                'status'        => SubmissionTypes::PENDING_REVIEW->value,
                            ]);

                            Log::info('Submission: file submitted successfully', array_merge($context, [
                                'event'         => 'submission_success',
                                'submission_id' => $submission->id,
                                'file_path'     => $fileDetails['file_path'],
                                'file_name'     => $fileDetails['file_name'],
                                'file_size_kb'  => round($fileDetails['file_size'] / 1024, 1),
                                'file_type'     => $fileDetails['file_type'],
                            ]));

                            Notification::make()
                                ->title('Submission Successful')
                                ->body('Your assignment has been submitted successfully.')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Log::error('Submission: error during file submission', array_merge($context, [
                                'event' => 'submission_error',
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]));

                            Notification::make()
                                ->title('Submission Failed')
                                ->body('An error occurred: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading('All Caught Up!')
            ->emptyStateDescription('You have no pending tasks. Great work!')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    public static function processSubmissionFile(
        array $data,
        Task  $record,
        bool  $isResubmit              = false,
        ?Submission $existingSubmission = null
    ): array {
        $sectionId    = $record->section->id;
        $taskId       = $record->id;
        $userName     = str_replace(' ', '_', Auth::user()->name);
        $timestamp    = now()->format('Y-m-d_H-i-s');

        $tempPath     = $data['file'];
        $finalDir     = "submissions/{$sectionId}/{$taskId}";
        $originalName = str_replace(' ', '_', $data['original_file_name'] ?? basename($tempPath));
        $sanitizedName = "{$userName}-{$timestamp}-{$originalName}";
        $newPath      = "{$finalDir}/{$sanitizedName}";

        Log::debug('Submission file processing: starting move', [
            'event'      => 'submission_file_move_start',
            'temp_path'  => $tempPath,
            'final_path' => $newPath,
            'disk'       => 'public',
            'candidate_id' => Auth::id(),
            'task_id'    => $taskId,
        ]);

        Storage::disk('public')->makeDirectory($finalDir);

        if (! Storage::disk('public')->exists($tempPath)) {
            Log::error('Submission file processing: temp file not found', [
                'event'     => 'submission_file_not_found',
                'temp_path' => $tempPath,
                'candidate_id' => Auth::id(),
                'task_id'   => $taskId,
            ]);
            throw new \RuntimeException("Uploaded file not found at: {$tempPath}");
        }

        Storage::disk('public')->move($tempPath, $newPath);

        $fileSize = Storage::disk('public')->size($newPath);
        $fileType = Storage::disk('public')->mimeType($newPath);

        Log::debug('Submission file processing: move complete', [
            'event'         => 'submission_file_move_complete',
            'final_path'    => $newPath,
            'file_size_kb'  => round($fileSize / 1024, 1),
            'file_type'     => $fileType,
            'candidate_id'  => Auth::id(),
            'task_id'       => $taskId,
        ]);

        // Clean up old file on resubmission
        if ($isResubmit && $existingSubmission) {
            $oldPath = $existingSubmission->getStoragePath();
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
                Log::info('Submission file processing: old file deleted on resubmit', [
                    'event'         => 'submission_old_file_deleted',
                    'old_path'      => $oldPath,
                    'submission_id' => $existingSubmission->id,
                    'candidate_id'  => Auth::id(),
                ]);
            }
        }

        return [
            'file_name' => $sanitizedName,
            'file_path' => $finalDir,
            'file_size' => $fileSize,
            'file_type' => $fileType,
        ];
    }
}
