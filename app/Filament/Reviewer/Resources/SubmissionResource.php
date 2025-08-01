<?php

namespace App\Filament\Reviewer\Resources;

use App\Enums\SubmissionTypes;
use App\Filament\Reviewer\Resources\SubmissionResource\Pages;
use App\Models\ReviewModificationRequest;
use App\Models\Submission;
use App\Models\ModificationRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
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

    public static function canCreate(): bool
    {
        return false;
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
                                ->helperText('Click the preview button (👁) to view the file content'),
//                                ->helperText('Click the download button (↓)'),

                            Forms\Components\Actions::make([




                                Forms\Components\Actions\Action::make('preview')
                                    ->label('Preview File')
                                    ->icon('heroicon-o-eye')
                                    ->color('primary')
                                    ->modalHeading('File Preview - Protected Content')
                                    ->modalDescription('This content is protected from copying, printing, and downloading.')
                                    ->modalContent(function (Submission $record) {
                                        $fileExtension = strtolower(pathinfo($record->file_name, PATHINFO_EXTENSION));

                                        if ($fileExtension === 'pdf') {
                                            $pdfUrl = route('reviewer.submissions.file', ['submission' => $record]);

                                            return new HtmlString('
<div class="pdf-preview-container" style="
    max-height: 70vh;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #f9fafb;
    position: relative;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
" oncontextmenu="return false;">
    <!-- Header -->
    <div style="
        padding: 12px 16px;
        background: #3b82f6;
        color: white;
        border-radius: 8px 8px 0 0;
        font-size: 14px;
        font-weight: 600;
    ">
        📄 ' . htmlspecialchars($record->file_name) . ' | 🔒 PROTECTED CONTENT
    </div>

    <!-- PDF Embed Container -->
    <div style="height: calc(70vh - 100px); position: relative; background: white;">
        <iframe
            src="' . $pdfUrl . '"
            style="width:100%;height:100%;border:none;"
            sandbox="allow-same-origin"
            title="Secure PDF Preview"
        ></iframe>
    </div>

    <!-- Footer notice -->
    <div style="
        padding: 8px 16px;
        background: #f3f4f6;
        border-radius: 0 0 8px 8px;
        font-size: 12px;
        color: #6b7280;
        text-align: center;
        border-top: 1px solid #e5e7eb;
    ">
        🔒 Protected document - Copying content is disabled
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const container = document.querySelector(".pdf-preview-container");

        // Disable right-click
        container.addEventListener("contextmenu", e => e.preventDefault());

        // Disable keyboard shortcuts
        document.addEventListener("keydown", e => {
            if (e.ctrlKey && (e.key === "c" || e.key === "s" || e.key === "p")) e.preventDefault();
            if (e.key === "F12") e.preventDefault();
        });

        // Add additional security for iframe
        const iframe = container.querySelector("iframe");
        iframe.addEventListener("load", function() {
            try {
                const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;

                // Disable text selection
                iframeDoc.addEventListener("selectstart", e => e.preventDefault());

                // Add watermark
                const watermark = iframeDoc.createElement("div");
                watermark.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    pointer-events: none;
                    background: repeating-linear-gradient(
                        45deg,
                        transparent,
                        transparent 100px,
                        rgba(59, 130, 246, 0.03) 100px,
                        rgba(59, 130, 246, 0.03) 200px
                    );
                    z-index: 1000;
                `;
                iframeDoc.body.appendChild(watermark);
            } catch (e) {
                console.log("Security restrictions prevent full protection");
            }
        });
    });
</script>
            ');
                                        }
                                    })
                                    ->modalWidth('7xl')
                                    ->modalSubmitAction(false)
                                    ->modalCancelActionLabel('Close')
                                    ->closeModalByClickingAway(false),



//
//                                Forms\Components\Actions\Action::make('open_pdf')
//                                    ->label('Open PDF')
//                                    ->icon('heroicon-o-arrow-top-right-on-square')
//                                    ->color('gray')
//                                    ->url(function (Submission $record) {
//                                        $fileExtension = strtolower(pathinfo($record->file_name, PATHINFO_EXTENSION));
//                                        if ($fileExtension === 'pdf') {
//                                            return route('reviewer.submissions.file', ['submission' => $record]);
//                                        }
//                                        return null;
//                                    })
//                                    ->openUrlInNewTab()
//                                    ->visible(function (Submission $record) {
//                                        $fileExtension = strtolower(pathinfo($record->file_name, PATHINFO_EXTENSION));
//                                        return $fileExtension === 'pdf';
//                                    })
//                                    ->tooltip('Open PDF in new browser tab')



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
            ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Primary information - always visible
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Submission $record): string =>
                        'District: ' . $record->student->district->name
                    )
                    ->wrap(),

                Tables\Columns\TextColumn::make('task.title')
                    ->label('Task')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Submission $record): string =>
                        $record->task->section->name
                    )
                    ->wrap()
                    ->limit(40),

                // Combined status column for mobile efficiency
                Tables\Columns\TextColumn::make('combined_status')
                    ->label('Review Status')
                    ->getStateUsing(function (Submission $record) {
                        $review = $record->reviews()->where('reviewer_id', auth()->id())->first();

                        if (!$review) {
                            return 'Not Started';
                        }

                        if (!$review->is_completed) {
                            return 'In Progress';
                        }

                        if ($review->hasApprovedModificationRequest()) {
                            return 'Modifiable';
                        }

                        if ($review->hasPendingModificationRequest()) {
                            return 'Mod. Pending';
                        }

                        return 'Completed';
                    })
                    ->badge()
                    ->colors([
                        'gray' => 'Not Started',
                        'warning' => 'In Progress',
                        'success' => 'Completed',
                        'info' => 'Modifiable',
                        'primary' => 'Mod. Pending',
                    ])
                    ->description(function (Submission $record) {
                        $review = $record->reviews()->where('reviewer_id', auth()->id())->first();
                        $score = $review && $review->score ? $review->score . '/' . $record->task->max_score : 'No score';
                        return $score;
                    }),

                // Hide these columns by default on mobile - make them toggleable
                Tables\Columns\TextColumn::make('student.id')
                    ->label('Student ID')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('student.district.name')
                    ->label('District')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('task.section.name')
                    ->label('Section')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('task.section.trainingProgram.name')
                    ->label('Program')
                    ->toggleable(isToggledHiddenByDefault: true)
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
                    ])
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('score')
                    ->label('Score')
                    ->getStateUsing(function (Submission $record) {
                        $review = $record->reviews()->where('reviewer_id', auth()->id())->first();
                        return $review && $review->score ? $review->score . '/' . $record->task->max_score : '-';
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('Reviewed At')
                    ->getStateUsing(function (Submission $record) {
                        $review = $record->reviews()->where('reviewer_id', auth()->id())->first();
                        return $review && $review->is_completed ? $review->updated_at : null;
                    })
                    ->dateTime()
                    ->since() // Shows "2 hours ago" instead of full date
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->defaultSort('created_at', 'desc')
            // Add these mobile-friendly configurations
            ->striped()
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10); // Smaller page sizes for mobile
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

    /**
     * Get file content for preview - Updated with PDF support
     */
//    protected static function getFileContent(Submission $record): ?string
//    {
//        if (!$record->file_path || !$record->file_name) {
//            return null;
//        }
//
//        $fullPath = $record->file_path . '/' . $record->file_name;
//
//        if (!Storage::exists($fullPath)) {
//            return null;
//        }
//
//        try {
//            $fileExtension = strtolower(pathinfo($record->file_name, PATHINFO_EXTENSION));
//
//            // Handle different file types
//            switch ($fileExtension) {
//                case 'pdf':
//                    // Option 1: Embed PDF using browser's native PDF viewer
//                    return static::getPdfPreviewHtml($record, $fullPath);
//
//                case 'txt':
//                case 'md':
//                case 'php':
//                case 'js':
//                case 'html':
//                case 'css':
//                case 'json':
//                case 'xml':
//                case 'yml':
//                case 'yaml':
//                    // Text-based files - return as is
//                    $content = Storage::get($fullPath);
//                    return $content;
//
//                case 'doc':
//                case 'docx':
//                    return 'Word documents cannot be previewed in text format. Please use the download option.';
//
//                default:
//                    $content = Storage::get($fullPath);
//                    // Try to detect if it's text content
//                    if (mb_check_encoding($content, 'UTF-8') && ctype_print(substr($content, 0, 1000))) {
//                        return $content;
//                    } else {
//                        return 'Binary file cannot be previewed in text format. Please use the download option.';
//                    }
//            }
//
//        } catch (\Exception $e) {
//            \Log::error('Error reading file for preview: ' . $e->getMessage(), [
//                'file_path' => $fullPath,
//                'submission_id' => $record->id
//            ]);
//
//            return 'Error reading file content. Please try downloading the file instead.';
//        }
//    }



    /**
     * Get file content for preview - Updated with better PDF detection
     */

    protected static function getFileContent(Submission $record): ?string
    {
        if (!$record->file_path || !$record->file_name) {
            \Log::warning('Missing file path or name', [
                'submission_id' => $record->id,
                'file_path' => $record->file_path,
                'file_name' => $record->file_name
            ]);
            return 'Error: Missing file path or filename';
        }

        $fullPath = $record->file_path . '/' . $record->file_name;

        \Log::info('Attempting to read file', [
            'submission_id' => $record->id,
            'full_path' => $fullPath,
            'storage_disk' => config('filesystems.default'),
            'file_exists' => Storage::exists($fullPath)
        ]);

        if (!Storage::exists($fullPath)) {
            // Try alternative paths
            $alternativePaths = [
                $record->file_name,
                'submissions/' . $record->file_name,
                'uploads/' . $record->file_name,
                'storage/app/' . $fullPath,
            ];

            foreach ($alternativePaths as $altPath) {
                if (Storage::exists($altPath)) {
                    \Log::info('Found file at alternative path', [
                        'original_path' => $fullPath,
                        'found_path' => $altPath
                    ]);
                    $fullPath = $altPath;
                    break;
                }
            }

            if (!Storage::exists($fullPath)) {
                \Log::error('File not found anywhere', [
                    'tried_paths' => array_merge([$fullPath], $alternativePaths)
                ]);
                return 'File not found at path: ' . $fullPath;
            }
        }

        try {
            $fileExtension = strtolower(pathinfo($record->file_name, PATHINFO_EXTENSION));

            if ($fileExtension === 'pdf') {
                $content = Storage::get($fullPath);
                $isPdf = substr($content, 0, 4) === '%PDF';

                return $isPdf ?
                    'PDF file detected (' . strlen($content) . ' bytes). Use PDF viewer.' :
                    'File is not a valid PDF. First 20 chars: ' . substr($content, 0, 20);
            }

            // Handle other file types
            switch ($fileExtension) {
                case 'txt':
                case 'md':
                case 'php':
                case 'js':
                case 'html':
                case 'css':
                case 'json':
                case 'xml':
                    $content = Storage::get($fullPath);
                    return strlen($content) > 500000 ?
                        substr($content, 0, 500000) . "\n\n[File truncated - too large]" :
                        $content;

                default:
                    $content = Storage::get($fullPath);
                    $sample = substr($content, 0, 1000);

                    if (mb_check_encoding($sample, 'UTF-8') && !preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $sample)) {
                        return strlen($content) > 500000 ?
                            substr($content, 0, 500000) . "\n\n[File truncated]" :
                            $content;
                    } else {
                        return 'Binary file (' . strtoupper($fileExtension) . ') - ' . strlen($content) . ' bytes';
                    }
            }

        } catch (\Exception $e) {
            \Log::error('Error reading file content', [
                'file_path' => $fullPath,
                'error' => $e->getMessage(),
                'submission_id' => $record->id
            ]);

            return 'Error reading file: ' . $e->getMessage();
        }
    }

    /**
     * Generate HTML for PDF preview - Fixed for proper loading
     */

    protected static function getPdfPreviewHtml(Submission $record, string $fullPath): string
    {
        // Get secure route-based URL
        $pdfUrl = route('reviewer.submissions.file', ['submission' => $record]);

        return '
<div class="pdf-preview-container" style="
    max-height: 70vh;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #f9fafb;
    position: relative;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
" oncontextmenu="return false;">
    <!-- Header -->
    <div style="
        padding: 12px 16px;
        background: #3b82f6;
        color: white;
        border-radius: 8px 8px 0 0;
        font-size: 14px;
        font-weight: 600;
    ">
        📄 ' . htmlspecialchars($record->file_name) . ' | 🔒 PROTECTED CONTENT
    </div>

    <!-- PDF Embed Container -->
    <div style="height: calc(70vh - 100px); position: relative; background: white;">
        <div id="pdf-viewer" style="width:100%;height:100%;overflow:auto;position:relative"></div>
        <div id="pdf-loading" style="
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #6b7280;
            font-size: 14px;
            z-index: 10;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        ">
            <div style="text-align: center;">
                <div style="margin-bottom: 10px; font-size: 24px;">📄</div>
                <div>Loading PDF...</div>
                <div style="margin-top: 8px; font-size: 12px; color: #9ca3af;">
                    This may take a moment
                </div>
            </div>
        </div>
    </div>

    <!-- Footer notice -->
    <div style="
        padding: 8px 16px;
        background: #f3f4f6;
        border-radius: 0 0 8px 8px;
        font-size: 12px;
        color: #6b7280;
        text-align: center;
        border-top: 1px solid #e5e7eb;
    ">
        🔒 Protected document - Copying content is disabled
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const container = document.querySelector(".pdf-preview-container");
    const viewer = document.getElementById("pdf-viewer");
    const loading = document.getElementById("pdf-loading");

    // Disable right-click
    container.addEventListener("contextmenu", e => e.preventDefault());

    // Disable keyboard shortcuts
    document.addEventListener("keydown", e => {
        if (e.ctrlKey && (e.key === "c" || e.key === "s" || e.key === "p")) e.preventDefault();
        if (e.key === "F12") e.preventDefault();
    });

    // Load PDF via server-side rendering
    fetch("' . $pdfUrl . '")
        .then(response => response.blob())
        .then(blob => {
            const reader = new FileReader();
            reader.onload = function() {
                const typedarray = new Uint8Array(this.result);

                // Load PDF.js from CDN if not already loaded
                if (typeof pdfjsLib === "undefined") {
                    const script = document.createElement("script");
                    script.src = "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js";
                    script.onload = function() {
                        pdfjsLib.GlobalWorkerOptions.workerSrc =
                            "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js";
                        renderPDF(typedarray);
                    };
                    document.head.appendChild(script);
                } else {
                    renderPDF(typedarray);
                }
            };
            reader.readAsArrayBuffer(blob);
        })
        .catch(error => {
            console.error("PDF loading error:", error);
            loading.innerHTML = `
                <div style="text-align:center;color:#ef4444;">
                    <div style="font-size:24px;">⚠️</div>
                    <p>Failed to load PDF</p>
                    <p style="font-size:12px;">${error.message}</p>
                </div>
            `;
        });

    function renderPDF(data) {
        pdfjsLib.getDocument({ data }).promise
            .then(pdf => {
                loading.style.display = "none";

                // Render first page
                pdf.getPage(1).then(page => {
                    const viewport = page.getViewport({ scale: 1.5 });
                    const canvas = document.createElement("canvas");
                    const ctx = canvas.getContext("2d");

                    canvas.height = viewport.height;
                    canvas.width = viewport.width;
                    viewer.appendChild(canvas);

                    // Add watermark
                    ctx.fillStyle = "rgba(0,0,0,0.1)";
                    ctx.font = "bold 48px Arial";
                    ctx.fillText("PROTECTED CONTENT", 50, viewport.height/2);

                    // Render page
                    page.render({
                        canvasContext: ctx,
                        viewport: viewport
                    });
                });
            })
            .catch(error => {
                console.error("PDF rendering error:", error);
                loading.innerHTML = `
                    <div style="text-align:center;color:#ef4444;">
                        <div style="font-size:24px;">⚠️</div>
                        <p>Failed to render PDF</p>
                        <p style="font-size:12px;">${error.message}</p>
                    </div>
                `;
            });
    }
});
</script>';
    }

}
