<?php

namespace App\Filament\Reviewer\Resources\SubmissionResource\Pages;

use App\Filament\Reviewer\Resources\SubmissionResource;
use App\Models\Review;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

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

    protected function handleRecordUpdate($record, array $data): \Illuminate\Database\Eloquent\Model
    {
        // Create or update review
        $review = Review::updateOrCreate(
            [
                'submission_id' => $record->id,
                'reviewer_id' => auth()->id(),
            ],
            [
                'is_completed' => $data['is_completed'] ?? false,
                'score' => $data['is_completed'] ? $data['score'] : null,
                'comments' => $data['comments'] ?? null,
                'reviewed_at' => now(),
            ]
        );

        // Update submission status
        $record->update([
            'status' => $data['is_completed'] ? 'completed' : 'needs_revision'
        ]);

        Notification::make()
            ->title('Review submitted successfully')
            ->success()
            ->send();

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
