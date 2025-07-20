<?php

namespace App\Filament\Reviewer\Resources\SubmissionResource\Pages;

use App\Enums\SubmissionTypes;
use App\Filament\Reviewer\Resources\SubmissionResource;
use App\Models\Review;
use Filament\Actions;
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
                ->modalDescription('Once you submit this review with "Completed" status, the score will be locked and cannot be changed. Are you sure you want to proceed?')
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
        }

        return $data;
    }

    protected function handleRecordUpdate($record, array $data): Model
    {
        try {
            $reviewStatus = $data['review_status'];
            $isCompleted = in_array($reviewStatus, [SubmissionTypes::COMPLETED->value]);

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
                SubmissionTypes::COMPLETED->value => 'Review completed successfully. The score has been locked.',
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return null;
    }
}
