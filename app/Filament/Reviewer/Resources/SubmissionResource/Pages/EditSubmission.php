<?php

namespace App\Filament\Reviewer\Resources\SubmissionResource\Pages;

use App\Enums\SubmissionTypes;
use App\Filament\Reviewer\Resources\SubmissionResource;
use App\Models\Review;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class EditSubmission extends EditRecord
{
    protected static string $resource = SubmissionResource::class;

//    protected function getHeaderActions(): array
//    {
//        return [
//            Actions\DeleteAction::make(),
//        ];
//    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $review = $this->record->reviews()->where('reviewer_id', auth()->id())->first();

        if ($review) {
            $data['is_completed'] = $review->is_completed;
            $data['score'] = $review->score;
            $data['comments'] = $review->comments;
        }

        return $data;
    }

    protected function handleRecordUpdate($record, array $data): Model
    {
        try {
            $isCompleted = $data['review_status'] === SubmissionTypes::COMPLETED->value;
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
                    'reviewed_at' => now(),
                ]
            );

            // Update submission status
            $record->update([
                'status' => $data['review_status']
            ]);

            if ($review) {
                Notification::make()
                    ->title('Review submitted successfully')
                    ->success()
                    ->send();
            }
            return $record;
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Unable to submit review. Please try again or contact the administrator')
                ->danger()
                ->send();
            Log::alert('Unable to submit review. Please try again or contact the administrator', [$e->getMessage()]);
            return  $record;
        }

    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
