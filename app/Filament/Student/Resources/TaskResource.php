<?php

namespace App\Filament\Student\Resources;

use App\Enums\SubmissionTypes;
use App\Filament\Student\Resources\TaskResource\Pages;
use App\Filament\Student\Resources\TaskResource\RelationManagers;
use App\Models\Submission;
use App\Models\Task;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'My Tasks';
    protected static ?string $navigationGroup = 'Submissions';
    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->isStudent();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('section.trainingProgram.enrollments', function ($query) {
                $query->where('student_id', Auth::user()->id)
                    ->where('status', 'active');
            })
            ->with(['section.trainingProgram', 'submissions' => function ($query) {
                $query->where('student_id', Auth::user()->id);
            }])
            ->orderBy('section_id', 'asc')
            ->orderBy('order_index', 'asc');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ViewColumn::make('task_info')
                    ->label('Details')
                    ->view('filament.student.table.task-description', static function ($record) {
                        return [
                            'title' => $record->title,
                            // 'program' => $record->section?->trainingProgram?->name,
                            'section' => $record->section?->name,
                        ];
                    }),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable()
                    ->color(function ($state) {
                        if (!$state) return 'gray';

                        $daysLeft = now()->diffInDays(Carbon::parse($state), false);

                        return match (true) {
                            $daysLeft < 0 => 'danger',      // Past due
                            $daysLeft == 0 => 'danger',     // Due today
                            $daysLeft <= 3 => 'warning',   // Due in 1-3 days
                            default => 'success'            // Due in more than 3 days
                        };
                    }),
                Tables\Columns\TextColumn::make('submission.status')
                    ->label('Status')
                    ->badge()
                    ->state(function ($record) {
                        return $record->submissions
                            ->firstWhere('student_id', Auth::user()->id)
                            ?->status ?? SubmissionTypes::PENDING_SUBMISSION->value;
                    })
                    ->color(function ($state) {
                        return match ($state) {
                            SubmissionTypes::PENDING_REVIEW->value => 'gray',
                            SubmissionTypes::UNDER_REVIEW->value => 'info',
                            SubmissionTypes::NEEDS_REVISION->value => 'warning',
                            SubmissionTypes::COMPLETED->value => 'success',
                            SubmissionTypes::FLAGGED->value => 'danger',
                            default => 'danger',
                        };
                    })
                    ->formatStateUsing(fn($state) => str($state)->title()),

//                Tables\Columns\TextColumn::make('submission_score')
//                    ->label('Score')
//                    ->getStateUsing(function ($record) {
//                        // Get the submission for current student
//                        $submission = $record->submissions
//                            ->where('student_id', Auth::user()->id)
//                            ->where('status', SubmissionTypes::COMPLETED->value)
//                            ->first();
//
//                        // Access score through the review relationship
//                        $score = $submission?->review?->score;
//
//                        return $score !== null
//                            ? round($score, 1) // Format to 1 decimal place
//                            : 'NG';
//                    })
//                    ->badge()
//                    ->color(function ($state) {
//                        if ($state === 'NG') return 'gray';
//
//                        $score = (float) str_replace('%', '', $state);
//
//                        return match (true) {
//                            $score >= 7.5 => 'success',
//                            $score >= 5 => 'warning',
//                            default => 'danger'
//                        };
//                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Submission Status')
                    ->options([
                        SubmissionTypes::PENDING_SUBMISSION->value => 'Pending Submission',
                        SubmissionTypes::PENDING_REVIEW->value => 'Pending Review',
                        SubmissionTypes::UNDER_REVIEW->value => 'Under Review',
                        SubmissionTypes::NEEDS_REVISION->value => 'Needs Revision',
                        SubmissionTypes::COMPLETED->value => 'Completed',
                        SubmissionTypes::FLAGGED->value => 'Flagged',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            SubmissionTypes::PENDING_SUBMISSION->value => $query->whereDoesntHave('submissions', function ($q) {
                                $q->where('student_id', Auth::user()->id);
                            }),
                            SubmissionTypes::COMPLETED->value => $query->whereHas('submissions', function ($q) {
                                $q->where('student_id', Auth::user()->id)
                                    ->where('status', SubmissionTypes::COMPLETED->value);
                            }),
                            SubmissionTypes::PENDING_REVIEW->value => $query->whereHas('submissions', function ($q) {
                                $q->where('student_id', Auth::user()->id)
                                    ->where('status', SubmissionTypes::PENDING_REVIEW->value);
                            }),
                            SubmissionTypes::UNDER_REVIEW->value => $query->whereHas('submissions', function ($q) {
                                $q->where('student_id', Auth::user()->id)
                                    ->where('status', SubmissionTypes::UNDER_REVIEW->value);
                            }),
                            SubmissionTypes::NEEDS_REVISION->value => $query->whereHas('submissions', function ($q) {
                                $q->where('student_id', Auth::user()->id)
                                    ->where('status', SubmissionTypes::NEEDS_REVISION->value);
                            }),
                            SubmissionTypes::FLAGGED->value => $query->whereHas('submissions', function ($q) {
                                $q->where('student_id', Auth::user()->id)
                                    ->where('status', SubmissionTypes::FLAGGED->value);
                            }),

                            default => $query,
                        };
                    }),
            ])
            ->actions([
                // Submit Action
                Tables\Actions\Action::make('submit')
                    ->label('Submit')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->visible(fn($record) => !$record->submissions->contains('student_id', Auth::id()))
                    ->form([
                        Forms\Components\Wizard::make([
                            Forms\Components\Wizard\Step::make('Upload File')
                                ->icon('heroicon-o-document-arrow-up')
                                ->schema([
                                    Forms\Components\FileUpload::make('file')
                                        ->label('Upload File')
                                        ->acceptedFileTypes([
                                            'application/pdf',
                                            'application/msword',
                                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                                        ])
                                        ->maxSize(10240)
                                        ->required()
                                        ->directory('submissions')
                                        ->preserveFilenames()
                                        ->disk('local')
                                        ->storeFileNamesIn('original_file_name')
                                        ->helperText('Accepted formats: PDF, DOC, DOCX. Max size: 10MB.'),

                                    Forms\Components\Textarea::make('notes')
                                        ->label('Student Notes (Optional)')
                                        ->rows(3)
                                        ->placeholder('Add any notes or comments...'),
                                ]),

                            Forms\Components\Wizard\Step::make('Confirm Submission')
                                ->icon('heroicon-o-check-circle')
                                ->schema([
                                    Forms\Components\Placeholder::make('confirmation')
                                        ->label('')
                                        ->content(function ($get) {
                                            $fileName = $get('original_file_name');
                                            $notes = $get('notes');

                                            return new \Illuminate\Support\HtmlString('
                                    <div class="text-center p-6">
                                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                                            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <h3 class="text-lg font-medium text-gray-900 mb-4">Ready to Submit</h3>
                                    </div>
                                ');
                                        }),

                                    Forms\Components\Checkbox::make('confirm_submission')
                                        ->label('I confirm that I want to submit this assignment')
                                        ->required()
                                        ->accepted(),
                                ]),
                        ])
                    ])
                    ->action(function ($record, $data) {
                        try {
                            $fileDetails = static::processSubmissionFile($data, $record);

                            Submission::create([
                                'task_id' => $record->id,
                                'student_id' => Auth::id(),
                                'content_text' => null,
                                'file_name' => $fileDetails['file_name'],
                                'file_path' => $fileDetails['file_path'],
                                'file_size' => $fileDetails['file_size'],
                                'file_type' => $fileDetails['file_type'],
                                'student_notes' => $data['notes'] ?? null,
                                'submitted_at' => now(),
                                'status' => SubmissionTypes::PENDING_REVIEW->value,
                            ]);

                            Notification::make()
                                ->title('Submission Successful')
                                ->body('Your assignment has been submitted successfully.')
                                ->success()
                                ->send();

                            return ['refresh' => true, 'close' => true];
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Submission Failed')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // Resubmit Action
                Tables\Actions\Action::make('resubmit')
                    ->label('Resubmit')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn($record) => $record->submissions->contains('student_id', Auth::id()) &&
                        $record->submissions->where('student_id', Auth::id())->first()->status !== SubmissionTypes::COMPLETED->value)
                    ->form([
                        Forms\Components\Wizard::make([
                            Forms\Components\Wizard\Step::make('Upload New File')
                                ->icon('heroicon-o-document-arrow-up')
                                ->schema([
                                    Forms\Components\FileUpload::make('file')
                                        ->label('Upload New File')
                                        ->acceptedFileTypes([
                                            'application/pdf',
                                            'application/msword',
                                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                                        ])
                                        ->maxSize(10240)
                                        ->required()
                                        ->directory('submissions')
                                        ->preserveFilenames()
                                        ->disk('local')
                                        ->storeFileNamesIn('original_file_name')
                                        ->helperText('Accepted formats: PDF, DOC, DOCX. Max size: 10MB.'),

                                    Forms\Components\Textarea::make('notes')
                                        ->label('Student Notes (Optional)')
                                        ->rows(3)
                                        ->placeholder('Add any notes or comments...'),
                                ]),

                            Forms\Components\Wizard\Step::make('Confirm Resubmission')
                                ->icon('heroicon-o-exclamation-triangle')
                                ->schema([
                                    Forms\Components\Placeholder::make('resubmit_confirmation')
                                        ->label('')
                                        ->content(function ($get) {
                                            $fileName = $get('original_file_name');
                                            $notes = $get('notes');

                                            return new \Illuminate\Support\HtmlString('
                                    <div class="text-center p-6">
                                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 mb-4">
                                            <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                            </svg>
                                        </div>
                                        <h3 class="text-lg font-medium text-gray-900 mb-4">Ready to Resubmit</h3>
                                    </div>
                                ');
                                        }),

                                    Forms\Components\Checkbox::make('confirm_resubmission')
                                        ->label('I understand this will replace my current submission')
                                        ->required()
                                        ->accepted(),
                                ]),
                        ])
                    ])
                    ->action(function ($record, $data) {
                        try {
                            $userId = Auth::id();
                            $existingSubmission = $record->submissions->where('student_id', $userId)->first();

                            if (!$existingSubmission) {
                                throw new \Exception("No existing submission found");
                            }

                            $fileDetails = static::processSubmissionFile($data, $record, true, $existingSubmission);

                            $existingSubmission->update([
                                'file_name' => $fileDetails['file_name'],
                                'file_path' => $fileDetails['file_path'],
                                'file_size' => $fileDetails['file_size'],
                                'file_type' => $fileDetails['file_type'],
                                'student_notes' => $data['notes'] ?? null,
                                'submitted_at' => now(),
                                'status' => SubmissionTypes::PENDING_REVIEW->value,
                            ]);

                            Notification::make()
                                ->title('Resubmission Successful')
                                ->body('Your assignment has been resubmitted successfully.')
                                ->success()
                                ->send();

                            return ['refresh' => true, 'close' => true];
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Resubmission Failed')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                // View Action
                // Tables\Actions\ViewAction::make()
                //     ->label('View Details')
                //     ->icon('heroicon-o-eye'),

                // View Submission Details Action
                Tables\Actions\Action::make('view_submission')
                    ->label('View Submission')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(function ($record) {
                        return $record->submissions()
                            ->where('student_id', Auth::id())
                            ->exists();
                    })
                    ->modalHeading(function ($record) {
                        $submission = $record->submissions()
                            ->where('student_id', Auth::id())
                            ->first();
                        return 'Submission Details';
                    })
                    ->modalContent(function ($record) {
                        $submission = $record->submissions()
                            ->where('student_id', Auth::id())
                            ->first();

                        if (!$submission) {
                            return view('filament.student.view-submission.no-submission-found');
                        }

                        return view('filament.student.view-submission.submission-details', compact('submission', 'record'));
                    })
                    ->modalActions([
                        \Filament\Actions\Action::make('download')
                            ->label('Download File')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->color('success')
                            ->visible(function ($record) {
                                $submission = $record->submissions()
                                    ->where('student_id', Auth::id())
                                    ->first();
                                return $submission && $submission->file_path;
                            })
                            ->url(function ($record) {
                                $submission = $record->submissions()
                                    ->where('student_id', Auth::id())
                                    ->first();
                                return $submission ? route('submission.download', $submission->id) : null;
                            })
                            ->openUrlInNewTab(),

                        \Filament\Actions\Action::make('edit_submission')
                            ->label('Edit Submission')
                            ->icon('heroicon-o-pencil')
                            ->color('warning')
                            ->visible(function ($record) {
                                $submission = $record->submissions()
                                    ->where('student_id', Auth::id())
                                    ->first();
                                // Only show if submission exists and deadline hasn't passed
                                return $submission &&
                                    $record->deadline &&
                                    now()->isBefore($record->deadline);
                            })
                            ->url(function ($record) {
                                return route('submission.edit', $record->id);
                            }),

                        \Filament\Actions\Action::make('close')
                            ->label('Close')
                            ->color('gray')
                            ->close(),
                    ])
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'view' => Pages\ViewTask::route('/{record}'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }

    public static function processSubmissionFile($data, $record, $isResubmit = false, $existingSubmission = null)
    {
        $sectionId = $record->section->id;
        $taskId = $record->id;
        $userId = Auth::id();
        $userName = str_replace(' ', '_', Auth::user()->name);
        $timestamp = now()->format('Y-m-d_H-i-s');

        // Define file paths
        $tempPath = $data['file'];
        $finalDir = "submissions/{$sectionId}/{$taskId}";
        $originalName = str_replace(' ', '_', $data['original_file_name']);
        $sanitizedName = "{$userName} - {$timestamp} - {$originalName}";
        $newPath = "{$finalDir}/{$sanitizedName}";

        // Ensure directory exists
        Storage::disk('local')->makeDirectory($finalDir);

        // Move file from temp to permanent location
        if (!Storage::disk('local')->exists($tempPath)) {
            throw new \Exception("Uploaded file not found at: {$tempPath}");
        }

        Storage::disk('local')->move($tempPath, $newPath);

        // Delete old file on resubmit
        if ($isResubmit && $existingSubmission && Storage::exists($existingSubmission->file_path)) {
            Storage::delete($existingSubmission->file_path);
        }

        // Return file details
        return [
            'file_name' => $sanitizedName,
            'file_path' => $finalDir,
            'file_size' => Storage::disk('local')->size($newPath),
            'file_type' => Storage::disk('local')->mimeType($newPath),
        ];
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canView($record): bool
    {
        return false;
    }
}
