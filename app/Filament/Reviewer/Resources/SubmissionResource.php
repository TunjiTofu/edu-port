<?php

namespace App\Filament\Reviewer\Resources;

use App\Enums\SubmissionTypes;
use App\Filament\Reviewer\Resources\SubmissionResource\Pages;
use App\Models\ReviewModificationRequest;
use App\Models\Submission;
use App\Models\ModificationRequest;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class SubmissionResource extends Resource
{
    protected static ?string $model = Submission::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Submissions to Review';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return Auth::user()?->isReviewer();
    }

    public static function getEloquentQuery(): Builder
    {
        $reviewer = auth()->user();

        return parent::getEloquentQuery()
            ->whereHas('reviews', function ($query) use ($reviewer) {
                $query->where('reviewer_id', $reviewer->id);
            })
            ->with([
                'student',
                'task.section.trainingProgram',
                'review',
                'reviews' => function ($query) use ($reviewer) {
                    $query->where('reviewer_id', $reviewer->id)
                        ->with('modificationRequests');
                }
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Student Details')
                    ->schema([
                        Forms\Components\TextInput::make('student.name')
                            ->label('Student Name')
                            ->formatStateUsing(function ($state, $record) {
                                return $record->student->name;
                            })
                            ->disabled(),

                        Forms\Components\TextInput::make('student.id')
                            ->label('Student ID')
                            ->formatStateUsing(function ($state, $record) {
                                return $record->student->id;
                            })
                            ->disabled(),

                        Forms\Components\TextInput::make('student.district.name')
                            ->label('Student District')
                            ->formatStateUsing(function ($state, $record) {
                                return $record->student->district->name;
                            })
                            ->disabled(),

                        Forms\Components\TextInput::make('student.church.name')
                            ->label('Student Church')
                            ->formatStateUsing(function ($state, $record) {
                                return $record->student->church->name;
                            })
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Submission Details')
                    ->schema([
                        Forms\Components\TextInput::make('task.title')
                            ->label('Task')
                            ->formatStateUsing(function ($state, $record) {
                                return $record->task->title;
                            })
                            ->disabled(),
                        Forms\Components\TextInput::make('task.section.title')
                            ->label('Section')
                            ->formatStateUsing(function ($state, $record) {
                                return $record->task->section->name;
                            })
                            ->disabled(),
                        Forms\Components\TextInput::make('task.section.trainingProgram.name')
                            ->label('Training Program')
                            ->formatStateUsing(function ($state, $record) {
                                return $record->task->section->trainingProgram->name;
                            })
                            ->disabled(),
                        Forms\Components\TextInput::make('task.max_score')
                            ->label('Maximum Score')
                            ->formatStateUsing(function ($state, $record) {
                                return $record->task->max_score . ' points';
                            })
                            ->disabled(),

                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('file_display')
                                ->label('Submitted File')
                                ->disabled()
                                ->formatStateUsing(function ($record) {
                                    return $record->file_path.'/'.$record->file_name;
                                })
                                ->helperText('Click the download button (↓)'),

                            Forms\Components\Actions::make([
                                Forms\Components\Actions\Action::make('download')
                                    ->icon('heroicon-o-arrow-down-tray')
                                    ->url(function (Submission $record) {
                                        return $record ? static::getDownloadUrl($record) : null;
                                    })
                                    ->openUrlInNewTab()
                            ])->fullWidth()
                        ]),

                        Forms\Components\Textarea::make('student_notes')
                            ->label('Student Notes')
                            ->disabled()
                            ->rows(3),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Similarity Check')
                    ->schema([
                        Forms\Components\Placeholder::make('similarity_score')
                            ->label('Similarity Score')
                            ->content(fn (Submission $record): string =>
                            $record->similarity_score ? $record->similarity_score . '%' : 'Not checked'
                            ),
                        Forms\Components\Textarea::make('similarity_details')
                            ->label('Similar Submissions')
                            ->disabled()
                            ->rows(2),
                    ])
                    ->columns(2)
                    ->visible(fn (Submission $record): bool => $record->similarity_score !== null),

                // Review Lock Status Section
                Forms\Components\Section::make('Review Status')
                    ->schema([
                        Forms\Components\Placeholder::make('review_lock_status')
                            ->label('Review Lock Status')
                            ->content(function (Submission $record) {
                                $review = $record->reviews()->where('reviewer_id', auth()->id())->first();

                                if (!$review || !$review->is_completed) {
                                    $content = '✅ Review not completed - editing allowed';
                                    $color = 'text-green-600 bg-green-50 border-green-200';
                                } elseif ($review->hasApprovedModificationRequest()) {
                                    $content = '✅ Admin has approved modification of this completed review';
                                    $color = 'text-green-600 bg-green-50 border-green-200';
                                } elseif ($review->hasPendingModificationRequest()) {
                                    $content = '⏳ Modification request pending admin approval';
                                    $color = 'text-amber-600 bg-amber-50 border-amber-200';
                                } else {
                                    $content = '🔒 Review completed - modification requires admin approval';
                                    $color = 'text-red-600 bg-red-50 border-red-200';
                                }

                                return new HtmlString(
                                    '<div class="p-3 rounded-lg border ' . $color . '">' .
                                    '<span class="font-medium">' . e($content) . '</span>' .
                                    '</div>'
                                );
                            })
                    ])
                    ->visible(function (Submission $record) {
                        $review = $record->reviews()->where('reviewer_id', auth()->id())->first();
                        return $review && $review->is_completed;
                    })
                    ->columns(1),

                // Modification Request Section
                Forms\Components\Section::make('Request Modification')
                    ->schema([
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('submit_modification_request')
                                ->label('Submit Modification Request')
                                ->icon('heroicon-o-pencil-square')
                                ->color('warning')
                                ->form([
                                    Forms\Components\Textarea::make('modification_reason')
                                        ->label('Reason for Modification')
                                        ->placeholder('Explain why you need to modify this completed review...')
                                        ->rows(3)
                                        ->required()
                                        ->validationMessages([
                                            'required' => 'Please provide a reason for requesting modification.',
                                        ]),
                                ])
                                ->action(function (Submission $record, array $data) {
                                    $review = $record->reviews()->where('reviewer_id', auth()->id())->first();

                                    if (!$review) {
                                        Notification::make()
                                            ->title('Error')
                                            ->body('No review found for this submission.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    // Create modification request
                                    ReviewModificationRequest::create([
                                        'review_id' => $review->id,
                                        'reviewer_id' => auth()->id(),
                                        'reason' => $data['modification_reason'],
                                        'status' => 'pending',
                                        'requested_at' => now(),
                                    ]);

                                    Notification::make()
                                        ->title('Modification Request Submitted')
                                        ->body('Your request has been sent to administrators for approval.')
                                        ->success()
                                        ->send();

                                    // Optionally redirect or refresh
//                                    $this->refreshFormData(['modification_history', 'review_lock_status']);
                                })
                                ->modalHeading('Request Modification')
                                ->modalDescription('Please explain why you need to modify this completed review.')
                                ->modalSubmitActionLabel('Submit Request')
                                ->closeModalByClickingAway(false)
                        ])->fullWidth()
                    ])
                    ->visible(function (Submission $record) {
                        $review = $record->reviews()->where('reviewer_id', auth()->id())->first();
                        return $review &&
                            $review->is_completed &&
                            !$review->hasApprovedModificationRequest() &&
                            !$review->hasPendingModificationRequest();
                    })
                    ->columns(1),

                // Modification History Section
                Forms\Components\Section::make('Modification History')
                    ->schema([
                        Forms\Components\Placeholder::make('modification_history')
                            ->label('')
                            ->content(function (Submission $record) {
                                $review = $record->reviews()->where('reviewer_id', auth()->id())->first();

                                if (!$review) {
                                    return 'No review found.';
                                }

                                $requests = $review->modificationRequests()
                                    ->orderBy('created_at', 'desc')
                                    ->get();

                                if ($requests->isEmpty()) {
                                    return 'No modification requests found.';
                                }

                                $html = '<div class="space-y-3">';

                                foreach ($requests as $request) {
                                    $statusColor = match($request->status) {
                                        'pending' => 'text-amber-600 dark:text-amber-400',
                                        'approved' => 'text-green-600 dark:text-green-400',
                                        'rejected' => 'text-red-600 dark:text-red-400',
                                        default => 'text-gray-600 dark:text-gray-400'
                                    };

                                    $statusIcon = match($request->status) {
                                        'pending' => '⏳',
                                        'approved' => '✅',
                                        'rejected' => '❌',
                                        default => '•'
                                    };

                                    $html .= '<div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">';
                                    $html .= '<div class="flex justify-between items-start">';
                                    $html .= '<span class="font-medium ' . $statusColor . '">' . $statusIcon . ' ' . ucfirst($request->status) . '</span>';
                                    $html .= '<span class="text-sm text-gray-700 dark:text-gray-200">' .
                                        ($request->requested_at ? $request->requested_at->format('M j, Y g:i A') :
                                            ($request->created_at ? $request->created_at->format('M j, Y g:i A') : 'Unknown date')) .
                                        '</span>';
                                    $html .= '</div>';
                                    $html .= '<div class="mt-2 text-sm text-gray-700 dark:text-gray-200">';
                                    $html .= '<strong>Reason:</strong> ' . e($request->reason);
                                    $html .= '</div>';

                                    $html .= '<div class="mt-2 text-sm text-gray-700 dark:text-gray-200">';
                                    $html .= '<strong>Admin\'s Comment:</strong> ' . e($request->admin_comments);
                                    $html .= '</div>';

                                    if ($request->admin_response) {
                                        $html .= '<div class="mt-2 text-sm text-gray-700 dark:text-gray-300">';
                                        $html .= '<strong>Admin Response:</strong> ' . e($request->admin_response);
                                        $html .= '</div>';
                                    }

                                    if ($request->reviewed_by) {
                                        $html .= '<div class="mt-1 text-xs text-gray-500 dark:text-gray-400">';
                                        $html .= 'Reviewed by: ' . e($request->reviewedBy->name ?? 'Unknown') . ' on ' .
                                            ($request->reviewed_at ? $request->reviewed_at->format('M j, Y g:i A') : 'N/A');
                                        $html .= '</div>';
                                    }

                                    $html .= '</div>';
                                }

                                $html .= '</div>';

                                return new HtmlString($html);
                            })
                    ])
                    ->visible(function (Submission $record) {
                        $review = $record->reviews()->where('reviewer_id', auth()->id())->first();
                        return $review && $review->modificationRequests()->exists();
                    })
                    ->columns(1),

                Forms\Components\Section::make('Review')
                    ->schema([
                        Forms\Components\Select::make('review_status')
                            ->label('Review Status')
                            ->options([
                                SubmissionTypes::COMPLETED->value => 'Mark as Completed',
                                SubmissionTypes::NEEDS_REVISION->value => 'Needs Revision',
                                SubmissionTypes::UNDER_REVIEW->value => 'Still in Review'
                            ])
                            ->default(SubmissionTypes::UNDER_REVIEW->value)
                            ->reactive()
                            ->required()
                            ->disabled(function (Submission $record) {
                                $review = $record->reviews()->where('reviewer_id', auth()->id())->first();
                                return $review && $review->is_completed && !$review->canBeModified();
                            })
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state === SubmissionTypes::UNDER_REVIEW->value) {
                                    $set('review.score', null);
                                }
                            }),

                        Forms\Components\TextInput::make('review.score')
                            ->label('Score')
                            ->formatStateUsing(function ($state, $record) {
                                return $record->score;
                            })
                            ->numeric()
                            ->inputMode('decimal')
                            ->step('0.1')
                            ->minValue(0)
                            ->maxValue(fn (Forms\Get $get, $record) => $record ? $record->task->max_score : 10)
                            ->visible(fn (Forms\Get $get): bool => in_array($get('review_status'), [SubmissionTypes::COMPLETED->value, SubmissionTypes::NEEDS_REVISION->value]))
                            ->required(fn (Forms\Get $get): bool => in_array($get('review_status'), [SubmissionTypes::COMPLETED->value, SubmissionTypes::NEEDS_REVISION->value]))
                            ->disabled(function (Submission $record) {
                                $review = $record->reviews()->where('reviewer_id', auth()->id())->first();
                                return $review && $review->is_completed && !$review->canBeModified();
                            })
                            ->validationMessages([
                                'numeric' => 'The score must be a valid number.',
                                'required' => 'Please provide a valid numeric score for this review.',
                                'min' => 'The score cannot be negative.',
                                'max' => 'The score cannot exceed the maximum allowed for this task.',
                            ])
                            ->helperText(fn ($record) => $record ? "Maximum score for this task: {$record->task->max_score} points" : '')
                            ->placeholder('Enter score (numbers only)'),

                        Forms\Components\Textarea::make('comments')
                            ->label('Review Comments')
                            ->required(fn (Forms\Get $get): bool => in_array($get('review_status'), [SubmissionTypes::COMPLETED->value, SubmissionTypes::NEEDS_REVISION->value]))
                            ->rows(4)
                            ->disabled(function (Submission $record) {
                                $review = $record->reviews()->where('reviewer_id', auth()->id())->first();
                                return $review && $review->is_completed && !$review->canBeModified();
                            })
                            ->helperText(function (Forms\Get $get) {
                                return match($get('review_status')) {
                                    'completed' => 'Provide feedback on why this submission was approved.',
                                    'needs_revision' => 'Explain what needs to be improved or corrected.',
                                    default => 'Optional comments about the submission.'
                                };
                            }),

                        Forms\Components\Toggle::make('notify_student')
                            ->label('Notify Student')
                            ->default(true)
                            ->helperText('Send email notification to student about the review')
                            ->visible(fn (Forms\Get $get): bool => in_array($get('review_status'), [SubmissionTypes::COMPLETED->value, SubmissionTypes::NEEDS_REVISION->value]))
                            ->disabled(function (Submission $record) {
                                $review = $record->reviews()->where('reviewer_id', auth()->id())->first();
                                return $review && $review->is_completed && !$review->canBeModified();
                            }),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.id')
                    ->label('Student ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.district.name')
                    ->label('District')
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('task.title')
                    ->label('Task')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('task.section.name')
                    ->label('Section')
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('task.section.trainingProgram.name')
                    ->label('Program')
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('submission.status')
                    ->label('Submission Status')
                    ->getStateUsing(function (Submission $record) {
                        return $record->status;
                    })
                    ->badge()
                    ->colors([
                        'gray' => SubmissionTypes::PENDING_REVIEW->value,
                        'info' => SubmissionTypes::UNDER_REVIEW->value,
                        'warning' => SubmissionTypes::NEEDS_REVISION->value,
                        'success' => SubmissionTypes::COMPLETED->value,
                        'danger' => SubmissionTypes::FLAGGED->value,
                    ]),

                Tables\Columns\TextColumn::make('review.is_completed')
                    ->label('Review Status')
                    ->getStateUsing(function (Submission $record) {
                        $review = $record->reviews()->where('reviewer_id', auth()->id())->first();

                        if (!$review) {
                            return 'Not Started';
                        }

                        if (!$review->is_completed) {
                            return 'In Progress';
                        }

                        // Check modification request status
                        if ($review->hasApprovedModificationRequest()) {
                            return 'Completed (Modifiable)';
                        }

                        if ($review->hasPendingModificationRequest()) {
                            return 'Completed (Mod. Pending)';
                        }

                        return 'Completed';
                    })
                    ->badge()
                    ->colors([
                        'gray' => 'Not Started',
                        'warning' => 'In Progress',
                        'success' => 'Completed',
                        'info' => 'Completed (Modifiable)',
                        'primary' => 'Completed (Mod. Pending)',
                    ]),

                Tables\Columns\TextColumn::make('score')
                    ->label('Score')
                    ->getStateUsing(function (Submission $record) {
                        $review = $record->reviews()->where('reviewer_id', auth()->id())->first();
                        return $review && $review->score ? $review->score . '/' . $record->task->max_score : '-';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('Reviewed At')
                    ->getStateUsing(function (Submission $record) {
                        $review = $record->reviews()->where('reviewer_id', auth()->id())->first();
                        return $review && $review->is_completed ? $review->updated_at : null;
                    })
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Submission Status')
                    ->options([
                        SubmissionTypes::PENDING_REVIEW->value => 'Pending Review',
                        SubmissionTypes::UNDER_REVIEW->value => 'Under Review',
                        SubmissionTypes::NEEDS_REVISION->value => 'Needs Revision',
                        SubmissionTypes::COMPLETED->value => 'Completed',
                        SubmissionTypes::FLAGGED->value => 'Flagged',
                    ]),

                Filter::make('review_status')
                    ->form([
                        Forms\Components\Select::make('review_completed')
                            ->label('Review Status')
                            ->options([
                                'not_started' => 'Not Started',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'modifiable' => 'Completed (Modifiable)',
                                'pending_mod' => 'Completed (Mod. Pending)',
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['review_completed'] ?? null,
                            function (Builder $query, $status) {
                                $reviewer = auth()->user();

                                return match($status) {
                                    'not_started' => $query->whereDoesntHave('reviews', function ($q) use ($reviewer) {
                                        $q->where('reviewer_id', $reviewer->id);
                                    }),
                                    'in_progress' => $query->whereHas('reviews', function ($q) use ($reviewer) {
                                        $q->where('reviewer_id', $reviewer->id)
                                            ->where('is_completed', false);
                                    }),
                                    'completed' => $query->whereHas('reviews', function ($q) use ($reviewer) {
                                        $q->where('reviewer_id', $reviewer->id)
                                            ->where('is_completed', true)
                                            ->whereDoesntHave('modificationRequests');
                                    }),
                                    'modifiable' => $query->whereHas('reviews', function ($q) use ($reviewer) {
                                        $q->where('reviewer_id', $reviewer->id)
                                            ->where('is_completed', true)
                                            ->whereHas('modificationRequests', function ($modQ) {
                                                $modQ->where('status', 'approved');
                                            });
                                    }),
                                    'pending_mod' => $query->whereHas('reviews', function ($q) use ($reviewer) {
                                        $q->where('reviewer_id', $reviewer->id)
                                            ->where('is_completed', true)
                                            ->whereHas('modificationRequests', function ($modQ) {
                                                $modQ->where('status', 'pending');
                                            });
                                    }),
                                    default => $query
                                };
                            }
                        );
                    }),

                SelectFilter::make('program')
                    ->relationship('task.section.trainingProgram', 'name')
                    ->label('Training Program'),

                SelectFilter::make('section')
                    ->relationship('task.section', 'name')
                    ->label('Section'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Review')
                    ->icon('heroicon-o-eye'),
            ])
            ->bulkActions([
                // You can add bulk actions here if needed
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Get download URL for submission file
     */
    protected static function getDownloadUrl(Submission $record): ?string
    {
        if (!$record->file_path || !$record->file_name) {
            return null;
        }

        $fullPath = $record->file_path . '/' . $record->file_name;

        if (!Storage::exists($fullPath)) {
            return null;
        }

        return Storage::temporaryUrl($fullPath, now()->addMinutes(30));
    }

    public static function getRelations(): array
    {
        return [
            // Add relations if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubmissions::route('/'),
            'edit' => Pages\EditSubmission::route('/{record}/edit'),
        ];
    }
}
