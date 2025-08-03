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
                        // Check if there's already a pending request
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

                // Show pending status if request exists
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
                ->color('primary') // Blue
                ->icon('heroicon-m-check')
                ->size(ActionSize::Large)
                ->visible(function () {
                    try {
                        $data = $this->form->getRawState(); // Use getRawState() to avoid validation issues
                        $reviewStatus = $data['review_status'] ?? '';
                        return !in_array($reviewStatus, [SubmissionTypes::COMPLETED->value, SubmissionTypes::NEEDS_REVISION->value]);
                    } catch (\Exception $e) {
                        return true; // Default to showing save button if there's an error
                    }
                })
                ->action(fn () => $this->save()),

            // Submit Review button - for "Mark as Completed" status
            Actions\Action::make('submit_completed')
                ->label('Submit Review')
                ->color('success') // Green
                ->icon('heroicon-m-check-circle')
                ->size(ActionSize::Large)
                ->visible(function () {
                    try {
                        $data = $this->form->getRawState(); // Use getRawState() to avoid validation issues
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
                ->color('warning') // Orange
                ->icon('heroicon-m-exclamation-triangle')
                ->size(ActionSize::Large)
                ->visible(function () {
                    try {
                        $data = $this->form->getRawState(); // Use getRawState() to avoid validation issues
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
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $review = $this->record->reviews()->where('reviewer_id', auth()->id())->first();

        if ($review) {
            $data['is_completed'] = $review->is_completed;
            $data['review']['score'] = $review->score;
            $data['comments'] = $review->comments;

            // Set the review status based on the submission status
            $data['review_status'] = $this->record->status;

            // Load rubrics data
//            if ($this->record->task->rubrics()->count() > 0) {
//                $rubrics = [];
//
//                foreach ($this->record->task->rubrics as $index => $taskRubric) {
//                    $reviewRubric = $review->reviewRubrics()
//                        ->where('rubric_id', $taskRubric->id)
//                        ->first();
//
//                    $rubrics[] = [
//                        'rubric_id' => $taskRubric->id,
//                        'is_checked' => $reviewRubric?->is_checked ?? false,
//                        'points_awarded' => $reviewRubric?->points_awarded ?? 0,
//                        'comments' => $reviewRubric?->comments ?? '',
//                    ];
//                }
//
//                $data['rubrics'] = $rubrics;
//            }

            try {
                // Check if task has rubrics method and rubrics exist
                if ($this->record->task && $this->record->task->rubrics()->exists()) {
                    $rubrics = [];

                    foreach ($this->record->task->rubrics as $taskRubric) {
                        $reviewRubric = ReviewRubric::where('review_id', $review->id)
                            ->where('rubric_id', $taskRubric->id)
                            ->first();

                        $rubrics[] = [
                            'rubric_id' => $taskRubric->id,
                            'rubric_title' => $taskRubric->title ?? $taskRubric->name,
                            'rubric_description' => $taskRubric->description,
                            'max_points' => $taskRubric->max_points ?? 0,
                            'is_checked' => $reviewRubric?->is_checked ?? false,
                            'points_awarded' => $reviewRubric?->points_awarded ?? 0,
                            'comments' => $reviewRubric?->comments ?? '',
                        ];
                    }

                    $data['rubrics'] = $rubrics;
                }
            } catch (\Exception $e) {
                // Silently handle cases where rubrics aren't set up yet
                \Log::info('Rubrics not available for task: ' . $this->record->task->id);
            }
        }

        return $data;
    }

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        $review = $this->record->reviews()->where('reviewer_id', auth()->id())->first();

        // If review is completed and user doesn't have permission to modify, show restricted form
        if ($review && $review->is_completed && !$review->canBeModified()) {
            // We'll handle this in getFormActions instead of blocking access completely
            return;
        }
    }

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

            // Check if this is a modification of a completed review
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

            // Handle rubrics data
            if (isset($data['rubrics']) && is_array($data['rubrics'])) {
                foreach ($data['rubrics'] as $rubricData) {
                    if (!isset($rubricData['rubric_id'])) continue;

                    ReviewRubric::updateOrCreate(
                        [
                            'review_id' => $review->id,
                            'rubric_id' => $rubricData['rubric_id'],
                        ],
                        [
                            'is_checked' => $rubricData['is_checked'] ?? false,
                            'points_awarded' => $rubricData['points_awarded'] ?? 0,
                            'comments' => $rubricData['comments'] ?? null,
                        ]
                    );
                }
            }

            // If this was a modification of a completed review with approved modification request,
            // mark the modification request as used
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
                // dispatch(new SendReviewNotificationJob($record, $review));
            }

            $successMessage = match ($reviewStatus) {
                SubmissionTypes::COMPLETED->value => $wasCompletedBefore && $hadApprovedModification
                    ? 'Review modification completed successfully. The score is now locked again.'
                    : 'Review completed successfully. The score has been locked.',
                SubmissionTypes::NEEDS_REVISION->value => 'Review submitted successfully. Student has been notified about required revisions.',
                default => 'Review saved successfully.'
            };

            Notification::make()
                ->title($successMessage)
                ->success()
                ->send();

            return $record;
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Unable to submit review')
                ->body('Please try again or contact the administrator')
                ->danger()
                ->send();

            Log::alert('Unable to submit review', [
                'error' => $e->getMessage(),
                'submission_id' => $record->id,
                'reviewer_id' => auth()->id()
            ]);

            $this->halt();
        }
    }

    // Auto-populate rubrics when form loads
    protected function afterFill(): void
    {
        $submission = $this->record;
        $review = $submission->reviews()->where('reviewer_id', auth()->id())->first();

        // Auto-create review rubrics if they don't exist
        if ($review && $submission->task->rubrics()->count() > 0) {
            foreach ($submission->task->rubrics as $taskRubric) {
                ReviewRubric::firstOrCreate(
                    [
                        'review_id' => $review->id,
                        'rubric_id' => $taskRubric->id,
                    ],
                    [
                        'is_checked' => false,
                        'points_awarded' => 0,
                        'comments' => null,
                    ]
                );
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return null;
    }
}
