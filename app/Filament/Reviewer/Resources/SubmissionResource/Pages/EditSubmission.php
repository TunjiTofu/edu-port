<?php

namespace App\Filament\Reviewer\Resources\SubmissionResource\Pages;

use App\Enums\SubmissionTypes;
use App\Filament\Reviewer\Resources\SubmissionResource;
use App\Models\Review;
use App\Models\ReviewModificationRequest;
use App\Models\ReviewRubric;
use Filament\Actions;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\ActionSize;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class EditSubmission extends EditRecord
{
    protected static string $resource = SubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),

            // Add quick rubrics sync action
            Actions\Action::make('sync_rubrics')
                ->label('Sync Rubrics')
                ->icon('heroicon-m-arrow-path')
                ->color('info')
                ->tooltip('Sync with task rubrics if new ones were added')
                ->action(function () {
                    $review = $this->record->reviews()->where('reviewer_id', auth()->id())->first();
                    if ($review) {
                        $review->syncRubrics();

                        Notification::make()
                            ->title('Rubrics Synchronized')
                            ->body('Review rubrics have been synchronized with task rubrics.')
                            ->success()
                            ->send();

                        $this->fillForm();
                    }
                })
                ->visible(function () {
                    $review = $this->record->reviews()->where('reviewer_id', auth()->id())->first();
                    return $review && !$review->is_completed && $this->record->task->rubrics()->count() > 0;
                }),
        ];
    }

    protected function getFormActions(): array
    {
        $review = $this->record->reviews()->where('reviewer_id', auth()->id())->first();

        // Check if review is completed and user doesn't have approval to modify
        if ($review && $review->is_completed && !$review->canBeModified()) {
            return [
                Actions\Action::make('request_modification')
                    ->label('Request Review Modification')
                    ->color('warning')
                    ->icon('heroicon-m-pencil-square')
                    ->size(ActionSize::Large)
                    ->form([
                        Section::make('Request Review Modification')
                            ->description('This review has been marked as completed. Please provide a reason for why you need to modify this completed review.')
                            ->schema([
                                Textarea::make('reason')
                                    ->label('Reason for Modification')
                                    ->placeholder('Please explain why you need to modify this completed review...')
                                    ->required()
                                    ->rows(4)
                                    ->helperText('This request will be sent to an administrator for approval.')
                            ])
                    ])
                    ->action(function (array $data) use ($review) {
                        if ($review->hasPendingModificationRequest()) {
                            Notification::make()
                                ->title('Request Already Pending')
                                ->body('You already have a pending modification request for this review. Please wait for admin approval.')
                                ->warning()
                                ->send();
                            return;
                        }

                        ReviewModificationRequest::create([
                            'review_id' => $review->id,
                            'reviewer_id' => auth()->id(),
                            'reason' => $data['reason'],
                            'status' => 'pending',
                        ]);

                        Notification::make()
                            ->title('Modification Request Submitted')
                            ->body('Your request has been sent to an administrator for review. You will be notified when a decision is made.')
                            ->success()
                            ->send();

                        return redirect($this->getResource()::getUrl('index'));
                    })
                    ->visible(fn () => !$review->hasPendingModificationRequest())
                    ->modalWidth('md'),

                Actions\Action::make('pending_request')
                    ->label('Modification Request Pending')
                    ->color('gray')
                    ->icon('heroicon-m-clock')
                    ->size(ActionSize::Large)
                    ->disabled()
                    ->visible(fn () => $review->hasPendingModificationRequest())
            ];
        }

        return [
            // Save Changes button - for "Still in Review" status
            Actions\Action::make('save_changes')
                ->label('Save Changes')
                ->color('primary')
                ->icon('heroicon-m-check')
                ->size(ActionSize::Large)
                ->visible(function () {
                    try {
                        $data = $this->form->getRawState();
                        $reviewStatus = $data['review_status'] ?? '';
                        return !in_array($reviewStatus, [SubmissionTypes::COMPLETED->value, SubmissionTypes::NEEDS_REVISION->value]);
                    } catch (\Exception $e) {
                        return true;
                    }
                })
                ->action(fn () => $this->save()),

            // Submit Review button - for "Mark as Completed" status
            Actions\Action::make('submit_completed')
                ->label('Submit Review')
                ->color('success')
                ->icon('heroicon-m-check-circle')
                ->size(ActionSize::Large)
                ->visible(function () {
                    try {
                        $data = $this->form->getRawState();
                        $reviewStatus = $data['review_status'] ?? '';
                        return $reviewStatus === SubmissionTypes::COMPLETED->value;
                    } catch (\Exception $e) {
                        return false;
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Confirm Review Completion')
                ->modalDescription(function () {
                    $review = $this->record->reviews()->where('reviewer_id', auth()->id())->first();
                    $isModification = $review && $review->is_completed && $review->hasApprovedModificationRequest();

                    if ($isModification) {
                        return 'You are about to save changes to this completed review. After saving, the review will be locked again and any future changes will require another admin approval. Are you sure you want to proceed?';
                    }

                    return 'Once you submit this review with "Completed" status, the score will be locked and cannot be changed without admin approval. Are you sure you want to proceed?';
                })
                ->modalSubmitActionLabel('Yes, Submit and Lock Score')
                ->modalIcon('heroicon-o-lock-closed')
                ->action(fn () => $this->save()),

            // Submit Review button - for "Needs Revision" status
            Actions\Action::make('submit_revision')
                ->label('Submit Review')
                ->color('warning')
                ->icon('heroicon-m-exclamation-triangle')
                ->size(ActionSize::Large)
                ->visible(function () {
                    try {
                        $data = $this->form->getRawState();
                        $reviewStatus = $data['review_status'] ?? '';
                        return $reviewStatus === SubmissionTypes::NEEDS_REVISION->value;
                    } catch (\Exception $e) {
                        return false;
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Confirm Review Submission')
                ->modalDescription('You are about to submit a review that requires revision. The student will be notified and can resubmit their work. Are you sure you want to proceed?')
                ->modalSubmitActionLabel('Yes, Submit Review')
                ->modalIcon('heroicon-o-exclamation-triangle')
                ->action(fn () => $this->save()),

            // Quick Score Calculator action
//            Actions\Action::make('calculate_score')
//                ->label('Calculate from Rubrics')
//                ->color('info')
//                ->icon('heroicon-m-calculator')
//                ->size(ActionSize::Medium)
//                ->tooltip('Auto-calculate score from current rubric selections')
//                ->action(function () {
//                    $review = $this->record->reviews()->where('reviewer_id', auth()->id())->first();
//                    if ($review) {
//                        $rubricScore = $review->getTotalRubricScore();
//
//                        // Update the form data
//                        $data = $this->form->getRawState();
//                        $data['review']['score'] = $rubricScore;
//                        $this->form->fill($data);
//
//                        Notification::make()
//                            ->title('Score Calculated')
//                            ->body("Score updated to {$rubricScore} based on current rubric selections.")
//                            ->success()
//                            ->send();
//                    }
//                })
//                ->visible(function () {
//                    $review = $this->record->reviews()->where('reviewer_id', auth()->id())->first();
//                    return $review &&
//                        !($review->is_completed && !$review->canBeModified()) &&
//                        $this->record->task->rubrics()->count() > 0;
//                }),

            // Replace your existing calculate_score action with this improved version:

            // Replace the existing calculate_score action in your getFormActions() method with this:

            Actions\Action::make('calculate_score')
                ->label('Calculate Score from Rubrics')
                ->color('info')
                ->icon('heroicon-m-calculator')
                ->size(ActionSize::Medium)
                ->tooltip('Auto-calculate score from current rubric selections')
                ->action(function () {
                    $review = $this->record->reviews()->where('reviewer_id', auth()->id())->first();
                    if (!$review) {
                        Notification::make()
                            ->title('Error')
                            ->body('No review found for this submission.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Get current form state to include any unsaved rubric changes
                    $currentData = $this->form->getRawState();

                    // Calculate score from current form rubrics data (not database)
                    $rubricScore = 0;
                    $checkedRubrics = 0;

                    if (isset($currentData['rubrics']) && is_array($currentData['rubrics'])) {
                        foreach ($currentData['rubrics'] as $rubricData) {
                            if (($rubricData['is_checked'] ?? false)) {
                                $points = $rubricData['points_awarded'] ?? 0;
                                $rubricScore += $points;
                                $checkedRubrics++;
                            }
                        }
                    }

                    // Update the form data structure properly
                    $currentData['review']['score'] = $rubricScore;

                    // Fill the form with updated data
                    $this->form->fill($currentData);

                    // Also update the component's data property for immediate UI update
                    $this->data = array_merge($this->data ?? [], $currentData);

                    Notification::make()
                        ->title('Score Calculated')
                        ->body("Score updated to {$rubricScore} from {$checkedRubrics} selected rubric criteria.")
                        ->success()
                        ->send();
                })
                ->visible(function () {
                    $review = $this->record->reviews()->where('reviewer_id', auth()->id())->first();
                    return $review &&
                        !($review->is_completed && !$review->canBeModified()) &&
                        $this->record->task->rubrics()->count() > 0;
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $review = $this->record->reviews()->where('reviewer_id', auth()->id())->first();

        if (!$review) {
            // Create a new review if it doesn't exist
            $review = Review::create([
                'submission_id' => $this->record->id,
                'reviewer_id' => auth()->id(),
                'is_completed' => false,
                'score' => 0,
                'comments' => null,
            ]);
        }

        // Sync rubrics to ensure we have all current task rubrics
        $review->syncRubrics();

        $data['is_completed'] = $review->is_completed;
        $data['review']['score'] = $review->score;
        $data['comments'] = $review->comments;
        $data['review_status'] = $this->record->status;

        // Load rubrics data with enhanced error handling
        try {
            if ($this->record->task && $this->record->task->rubrics()->exists()) {
                $rubrics = [];
                $taskRubrics = $this->record->task->rubrics()->orderBy('order_index')->get();

                foreach ($taskRubrics as $taskRubric) {
                    $reviewRubric = ReviewRubric::where('review_id', $review->id)
                        ->where('rubric_id', $taskRubric->id)
                        ->first();

                    $rubrics[] = [
                        'rubric_id' => $taskRubric->id,
                        'rubric_title' => $taskRubric->title,
                        'rubric_description' => $taskRubric->description,
                        'max_points' => $taskRubric->max_points,
                        'is_checked' => $reviewRubric?->is_checked ?? false,
                        'points_awarded' => $reviewRubric?->points_awarded ?? 0,
                        'comments' => $reviewRubric?->comments ?? '',
                    ];
                }

                $data['rubrics'] = $rubrics;
            }
        } catch (\Exception $e) {
            Log::warning('Error loading rubrics data', [
                'task_id' => $this->record->task->id,
                'review_id' => $review->id,
                'error' => $e->getMessage()
            ]);

            // Still continue with empty rubrics rather than failing
            $data['rubrics'] = [];
        }

        return $data;
    }

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();
        // Additional authorization logic can be added here
    }

//    protected function handleRecordUpdate($record, array $data): Model
//    {
//        try {
//            $review = $record->reviews()->where('reviewer_id', auth()->id())->first();
//
//            // Additional check before saving
//            if ($review && $review->is_completed && !$review->canBeModified()) {
//                Notification::make()
//                    ->title('Review Modification Not Allowed')
//                    ->body('This review has been completed and requires admin approval to modify.')
//                    ->danger()
//                    ->send();
//                $this->halt();
//            }
//
//            $reviewStatus = $data['review_status'];
//            $isCompleted = in_array($reviewStatus, [SubmissionTypes::COMPLETED->value]);
//
//            // Check for score consistency before completion
//            if ($isCompleted && isset($data['rubrics']) && !empty($data['rubrics'])) {
//                $rubricTotal = 0;
//                foreach ($data['rubrics'] as $rubricData) {
//                    if (($rubricData['is_checked'] ?? false)) {
//                        $rubricTotal += $rubricData['points_awarded'] ?? 0;
//                    }
//                }
//
//                $manualScore = $data['review']['score'] ?? 0;
//                $scoreDifference = abs($manualScore - $rubricTotal);
//
//                // Warn if there's a significant discrepancy
//                if ($scoreDifference > 0.5) {
//                    Log::info('Score discrepancy detected', [
//                        'submission_id' => $record->id,
//                        'manual_score' => $manualScore,
//                        'rubric_score' => $rubricTotal,
//                        'difference' => $scoreDifference
//                    ]);
//                }
//            }
//
//            $wasCompletedBefore = $review && $review->is_completed;
//            $hadApprovedModification = $review && $review->hasApprovedModificationRequest();
//
//            // Create or update review
//            $review = Review::updateOrCreate(
//                [
//                    'submission_id' => $record->id,
//                    'reviewer_id' => auth()->id(),
//                ],
//                [
//                    'is_completed' => $isCompleted,
//                    'score' => $data['review']['score'] ?? 0.0,
//                    'comments' => $data['comments'] ?? null,
//                    'reviewed_at' => $isCompleted ? now() : null,
//                ]
//            );
//
//            // Handle rubrics data with enhanced validation
//            if (isset($data['rubrics']) && is_array($data['rubrics'])) {
//                foreach ($data['rubrics'] as $rubricData) {
//                    if (!isset($rubricData['rubric_id'])) continue;
//
//                    // Validate points don't exceed maximum
//                    $taskRubric = $record->task->rubrics()->find($rubricData['rubric_id']);
//                    $maxPoints = $taskRubric?->max_points ?? 0;
//                    $pointsAwarded = min($rubricData['points_awarded'] ?? 0, $maxPoints);
//
//                    // If points awarded but not checked, auto-check
//                    $isChecked = $rubricData['is_checked'] ?? false;
//                    if ($pointsAwarded > 0 && !$isChecked) {
//                        $isChecked = true;
//                    }
//                    // If checked but no points, award max points
//                    elseif ($isChecked && $pointsAwarded == 0) {
//                        $pointsAwarded = $maxPoints;
//                    }
//
//                    ReviewRubric::updateOrCreate(
//                        [
//                            'review_id' => $review->id,
//                            'rubric_id' => $rubricData['rubric_id'],
//                        ],
//                        [
//                            'is_checked' => $isChecked,
//                            'points_awarded' => $pointsAwarded,
//                            'comments' => $rubricData['comments'] ?? null,
//                        ]
//                    );
//                }
//            }
//
//            // Handle modification request consumption
//            if ($wasCompletedBefore && $hadApprovedModification && $isCompleted) {
//                $review->consumeModificationRequest();
//
//                Log::info('Review modification request consumed', [
//                    'review_id' => $review->id,
//                    'reviewer_id' => auth()->id(),
//                    'submission_id' => $record->id,
//                ]);
//            }
//
//            // Update submission status
//            $record->update([
//                'status' => $reviewStatus
//            ]);
//
//            // Send notification email if needed
//            if ($isCompleted && ($data['notify_student'] ?? false)) {
//                // TODO: Implement email notification logic
//                Log::info('Email notification should be sent', [
//                    'submission_id' => $record->id,
//                    'student_id' => $record->student_id,
//                ]);
//            }
//
//            // Generate success message with score information
//            $successMessage = match ($reviewStatus) {
//                SubmissionTypes::COMPLETED->value => $wasCompletedBefore && $hadApprovedModification
//                    ? 'Review modification completed successfully. The score is now locked again.'
//                    : 'Review completed successfully. The score has been locked.',
//                SubmissionTypes::NEEDS_REVISION->value => 'Review submitted successfully. Student has been notified about required revisions.',
//                default => 'Review saved successfully.'
//            };
//
//            // Add score info to message if relevant
//            if ($isCompleted && $review->score) {
//                $scoreInfo = " Final score: {$review->score}/{$record->task->max_score}";
//                if ($record->task->rubrics()->count() > 0) {
//                    $rubricScore = $review->getTotalRubricScore();
//                    if (abs($review->score - $rubricScore) < 0.01) {
//                        $scoreInfo .= " (matches rubric total)";
//                    } else {
//                        $scoreInfo .= " (rubric total: {$rubricScore})";
//                    }
//                }
//                $successMessage .= $scoreInfo;
//            }
//
//            Notification::make()
//                ->title($successMessage)
//                ->success()
//                ->send();
//
//            return $record;
//
//        } catch (\Throwable $e) {
//            Notification::make()
//                ->title('Unable to submit review')
//                ->body('Please try again or contact the administrator. Error: ' . $e->getMessage())
//                ->danger()
//                ->send();
//
//            Log::alert('Unable to submit review', [
//                'error' => $e->getMessage(),
//                'submission_id' => $record->id,
//                'reviewer_id' => auth()->id(),
//                'trace' => $e->getTraceAsString()
//            ]);
//
//            $this->halt();
//        }
//    }


    protected function handleRecordUpdate($record, array $data): Model
    {
        try {
            $review = $record->reviews()->where('reviewer_id', auth()->id())->first();

            // Additional check before saving
            if ($review && $review->is_completed && !$review->canBeModified()) {
                Notification::make()
                    ->title('Review Modification Not Allowed')
                    ->body('This review has been completed and requires admin approval to modify.')
                    ->danger()
                    ->send();
                $this->halt();
            }

            $reviewStatus = $data['review_status'];
            $isCompleted = in_array($reviewStatus, [SubmissionTypes::COMPLETED->value]);

            // REMOVED: Score consistency check here since it's now in beforeSave()
            // This prevents duplicate validation and ensures it blocks before saving

            $wasCompletedBefore = $review && $review->is_completed;
            $hadApprovedModification = $review && $review->hasApprovedModificationRequest();

            // Create or update review
            $review = Review::updateOrCreate(
                [
                    'submission_id' => $record->id,
                    'reviewer_id' => auth()->id(),
                ],
                [
                    'is_completed' => $isCompleted,
                    'score' => $data['review']['score'] ?? 0.0,
                    'comments' => $data['comments'] ?? null,
                    'reviewed_at' => $isCompleted ? now() : null,
                ]
            );

            // Handle rubrics data with enhanced validation
            if (isset($data['rubrics']) && is_array($data['rubrics'])) {
                foreach ($data['rubrics'] as $rubricData) {
                    if (!isset($rubricData['rubric_id'])) continue;

                    // Validate points don't exceed maximum
                    $taskRubric = $record->task->rubrics()->find($rubricData['rubric_id']);
                    $maxPoints = $taskRubric?->max_points ?? 0;
                    $pointsAwarded = min($rubricData['points_awarded'] ?? 0, $maxPoints);

                    // If points awarded but not checked, auto-check
                    $isChecked = $rubricData['is_checked'] ?? false;
                    if ($pointsAwarded > 0 && !$isChecked) {
                        $isChecked = true;
                    }
                    // If checked but no points, award max points
                    elseif ($isChecked && $pointsAwarded == 0) {
                        $pointsAwarded = $maxPoints;
                    }

                    ReviewRubric::updateOrCreate(
                        [
                            'review_id' => $review->id,
                            'rubric_id' => $rubricData['rubric_id'],
                        ],
                        [
                            'is_checked' => $isChecked,
                            'points_awarded' => $pointsAwarded,
                            'comments' => $rubricData['comments'] ?? null,
                        ]
                    );
                }
            }

            // Handle modification request consumption
            if ($wasCompletedBefore && $hadApprovedModification && $isCompleted) {
                $review->consumeModificationRequest();

                Log::info('Review modification request consumed', [
                    'review_id' => $review->id,
                    'reviewer_id' => auth()->id(),
                    'submission_id' => $record->id,
                ]);
            }

            // Update submission status
            $record->update([
                'status' => $reviewStatus
            ]);

            // Send notification email if needed
            if ($isCompleted && ($data['notify_student'] ?? false)) {
                // TODO: Implement email notification logic
                Log::info('Email notification should be sent', [
                    'submission_id' => $record->id,
                    'student_id' => $record->student_id,
                ]);
            }

            // Generate success message with score information
            $successMessage = match ($reviewStatus) {
                SubmissionTypes::COMPLETED->value => $wasCompletedBefore && $hadApprovedModification
                    ? 'Review modification completed successfully. The score is now locked again.'
                    : 'Review completed successfully. The score has been locked.',
                SubmissionTypes::NEEDS_REVISION->value => 'Review submitted successfully. Student has been notified about required revisions.',
                default => 'Review saved successfully.'
            };

            // Add score info to message if relevant
            if ($isCompleted && $review->score) {
                $scoreInfo = " Final score: {$review->score}/{$record->task->max_score}";
                if ($record->task->rubrics()->count() > 0) {
                    $rubricScore = $review->getTotalRubricScore();
                    // Since we validated they match, this should always show "matches rubric total"
                    $scoreInfo .= " (matches rubric total: {$rubricScore})";
                }
                $successMessage .= $scoreInfo;
            }

            Notification::make()
                ->title($successMessage)
                ->success()
                ->send();

            return $record;

        } catch (\Throwable $e) {
            Notification::make()
                ->title('Unable to submit review')
                ->body('Please try again or contact the administrator. Error: ' . $e->getMessage())
                ->danger()
                ->send();

            Log::alert('Unable to submit review', [
                'error' => $e->getMessage(),
                'submission_id' => $record->id,
                'reviewer_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->halt();
        }
    }

    protected function afterFill(): void
    {
        $submission = $this->record;
        $review = $submission->reviews()->where('reviewer_id', auth()->id())->first();

        // Ensure review exists and rubrics are synced
        if ($review && $submission->task->rubrics()->count() > 0) {
            $review->syncRubrics();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return null; // We handle notifications in handleRecordUpdate
    }

    /**
     * Custom method to validate rubrics before saving
     */
    protected function validateRubrics(array $data): array
    {
        $errors = [];

        if (isset($data['rubrics']) && is_array($data['rubrics'])) {
            foreach ($data['rubrics'] as $index => $rubricData) {
                $rubricId = $rubricData['rubric_id'] ?? null;
                $isChecked = $rubricData['is_checked'] ?? false;
                $pointsAwarded = $rubricData['points_awarded'] ?? 0;

                if (!$rubricId) continue;

                $taskRubric = $this->record->task->rubrics()->find($rubricId);
                if (!$taskRubric) {
                    $errors[] = "Rubric #". ($index + 1) . ": Invalid rubric reference";
                    continue;
                }

                // Validate points don't exceed maximum
                if ($pointsAwarded > $taskRubric->max_points) {
                    $errors[] = "Rubric '{$taskRubric->title}': Points awarded ({$pointsAwarded}) cannot exceed maximum ({$taskRubric->max_points})";
                }

                // Validate points are not negative
                if ($pointsAwarded < 0) {
                    $errors[] = "Rubric '{$taskRubric->title}': Points awarded cannot be negative";
                }

                // Validate checked status consistency
                if ($isChecked && $pointsAwarded == 0) {
                    $errors[] = "Rubric '{$taskRubric->title}': Criteria is checked but no points awarded";
                }
            }
        }

        return $errors;
    }

    /**
     * Before save validation
     */
//    protected function beforeSave(): void
//    {
//        $data = $this->form->getState();
//
//        // Validate rubrics
//        $rubricErrors = $this->validateRubrics($data);
//        if (!empty($rubricErrors)) {
//            $errorMessage = "Rubric validation errors:\n" . implode("\n", $rubricErrors);
//
//            Notification::make()
//                ->title('Rubric Validation Errors')
//                ->body($errorMessage)
//                ->danger()
//                ->persistent()
//                ->send();
//
//            $this->halt();
//        }
//
//        // Additional validation for completed reviews
//        $reviewStatus = $data['review_status'] ?? '';
//        if (in_array($reviewStatus, [SubmissionTypes::COMPLETED->value, SubmissionTypes::NEEDS_REVISION->value])) {
//
//            // Ensure score is provided
//            $score = $data['review']['score'] ?? 0;
//            if ($score <= 0) {
//                Notification::make()
//                    ->title('Score Required')
//                    ->body('Please provide a valid score for completed reviews.')
//                    ->warning()
//                    ->send();
//
//                $this->halt();
//            }
//
//            // Ensure comments are provided
//            $comments = trim($data['comments'] ?? '');
//            if (empty($comments)) {
//                Notification::make()
//                    ->title('Comments Required')
//                    ->body('Please provide review comments for completed reviews.')
//                    ->warning()
//                    ->send();
//
//                $this->halt();
//            }
//
//            // Check if all rubrics have been evaluated (if rubrics exist)
//            if (isset($data['rubrics']) && !empty($data['rubrics'])) {
//                $totalRubrics = count($data['rubrics']);
//                $evaluatedRubrics = 0;
//
//                foreach ($data['rubrics'] as $rubricData) {
//                    if (isset($rubricData['is_checked']) || !empty($rubricData['comments'])) {
//                        $evaluatedRubrics++;
//                    }
//                }
//
//                if ($evaluatedRubrics < $totalRubrics) {
//                    $unevaluated = $totalRubrics - $evaluatedRubrics;
//
//                    Notification::make()
//                        ->title('Incomplete Rubric Evaluation')
//                        ->body("You have {$unevaluated} rubric criteria that haven't been evaluated. Please complete all rubrics before submitting.")
//                        ->warning()
//                        ->send();
//
//                    $this->halt();
//                }
//            }
//        }
//    }



    protected function beforeSave(): void
    {
        $data = $this->form->getState();

        // Validate rubrics
        $rubricErrors = $this->validateRubrics($data);
        if (!empty($rubricErrors)) {
            $errorMessage = "Rubric validation errors:\n" . implode("\n", $rubricErrors);

            Notification::make()
                ->title('Rubric Validation Errors')
                ->body($errorMessage)
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        // NEW: Validate score consistency
        $scoreErrors = $this->validateScoreConsistency($data);
        if (!empty($scoreErrors)) {
            $errorMessage = implode("\n\n", $scoreErrors);

            Notification::make()
                ->title('Score Validation Error')
                ->body($errorMessage)
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        // Additional validation for completed reviews
        $reviewStatus = $data['review_status'] ?? '';
        if (in_array($reviewStatus, [SubmissionTypes::COMPLETED->value, SubmissionTypes::NEEDS_REVISION->value])) {

            // Ensure score is provided
            $score = $data['review']['score'] ?? 0;
            if ($score <= 0) {
                Notification::make()
                    ->title('Score Required')
                    ->body('Please provide a valid score for completed reviews.')
                    ->warning()
                    ->send();

                $this->halt();
            }

            // Ensure comments are provided
            $comments = trim($data['comments'] ?? '');
            if (empty($comments)) {
                Notification::make()
                    ->title('Comments Required')
                    ->body('Please provide review comments for completed reviews.')
                    ->warning()
                    ->send();

                $this->halt();
            }

            // Check if all rubrics have been evaluated (if rubrics exist)
            if (isset($data['rubrics']) && !empty($data['rubrics'])) {
                $totalRubrics = count($data['rubrics']);
                $evaluatedRubrics = 0;

                foreach ($data['rubrics'] as $rubricData) {
                    if (isset($rubricData['is_checked']) || !empty($rubricData['comments'])) {
                        $evaluatedRubrics++;
                    }
                }

                if ($evaluatedRubrics < $totalRubrics) {
                    $unevaluated = $totalRubrics - $evaluatedRubrics;

                    Notification::make()
                        ->title('Incomplete Rubric Evaluation')
                        ->body("You have {$unevaluated} rubric criteria that haven't been evaluated. Please complete all rubrics before submitting.")
                        ->warning()
                        ->send();

                    $this->halt();
                }
            }
        }
    }

    /**
     * Get rubric statistics for debugging/logging
     */
    protected function getRubricStatistics(): array
    {
        $review = $this->record->reviews()->where('reviewer_id', auth()->id())->first();

        if (!$review) {
            return ['error' => 'No review found'];
        }

        $taskRubrics = $this->record->task->rubrics()->count();
        $reviewRubrics = $review->reviewRubrics()->count();
        $checkedRubrics = $review->reviewRubrics()->where('is_checked', true)->count();
        $totalPoints = $review->getTotalRubricScore();
        $maxPossiblePoints = $this->record->task->rubrics()->sum('max_points');

        return [
            'task_rubrics' => $taskRubrics,
            'review_rubrics' => $reviewRubrics,
            'checked_rubrics' => $checkedRubrics,
            'total_points' => $totalPoints,
            'max_possible_points' => $maxPossiblePoints,
            'completion_percentage' => $taskRubrics > 0 ? ($checkedRubrics / $taskRubrics) * 100 : 100,
            'score_percentage' => $maxPossiblePoints > 0 ? ($totalPoints / $maxPossiblePoints) * 100 : 0,
        ];
    }


    /**
     * Validate score matches rubrics total
     */
    protected function validateScoreConsistency(array $data): array
    {
        $errors = [];

        // Only validate if rubrics exist and review is being completed
        $reviewStatus = $data['review_status'] ?? '';
        if (!in_array($reviewStatus, [SubmissionTypes::COMPLETED->value, SubmissionTypes::NEEDS_REVISION->value])) {
            return $errors;
        }

        if (isset($data['rubrics']) && !empty($data['rubrics'])) {
            $rubricTotal = 0;
            $checkedRubrics = [];

            foreach ($data['rubrics'] as $rubricData) {
                if (($rubricData['is_checked'] ?? false)) {
                    $points = $rubricData['points_awarded'] ?? 0;
                    $rubricTotal += $points;
                    $checkedRubrics[] = [
                        'title' => $rubricData['rubric_title'] ?? 'Unknown',
                        'points' => $points
                    ];
                }
            }

            $manualScore = $data['review']['score'] ?? 0;
            $scoreDifference = abs($manualScore - $rubricTotal);

            // Block submission if scores don't match (allowing for minor floating point differences)
            if ($scoreDifference > 0.01) {
                $rubricBreakdown = '';
                if (!empty($checkedRubrics)) {
                    $rubricBreakdown = "\n\nRubric Breakdown:\n";
                    foreach ($checkedRubrics as $rubric) {
                        $rubricBreakdown .= "• {$rubric['title']}: {$rubric['points']} points\n";
                    }
                    $rubricBreakdown .= "Total from Rubrics: {$rubricTotal}";
                }

                $errors[] = "Score Mismatch: Manual score ({$manualScore}) does not match rubrics total ({$rubricTotal}). Use 'Calculate from Rubrics' button to auto-calculate, or Manually adjust the score to match the rubrics total.";
            }
        }

        return $errors;
    }
}
