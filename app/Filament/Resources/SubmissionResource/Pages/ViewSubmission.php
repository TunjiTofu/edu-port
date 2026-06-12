<?php

namespace App\Filament\Resources\SubmissionResource\Pages;

use App\Enums\RoleTypes;
use App\Enums\SubmissionTypes;
use App\Filament\Resources\SubmissionResource;
use App\Models\Review;
use App\Models\Role;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSubmission extends ViewRecord
{
    protected static string $resource = SubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ── Quick Assign/Reassign Reviewer ──────────────────────────────
            Actions\Action::make('assign_reviewer')
                ->label(fn () => $this->record->review?->reviewer_id ? 'Reassign Reviewer' : 'Assign Reviewer')
                ->icon('heroicon-o-user-plus')
                ->color(fn () => $this->record->review?->reviewer_id ? 'warning' : 'success')
                ->form([
                    Forms\Components\Select::make('reviewer_id')
                        ->label('Reviewer')
                        ->options(fn () =>
                        User::where('role_id', Role::where('name', RoleTypes::REVIEWER->value)->first()?->id)
                            ->where('is_active', true)
                            ->pluck('name', 'id')
                        )
                        ->searchable()
                        ->required()
                        ->default(fn () => $this->record->review?->reviewer_id),
                ])
                ->action(function (array $data) {
                    $review = $this->record->review;

                    if ($review) {
                        $review->update(['reviewer_id' => $data['reviewer_id']]);
                    } else {
                        $review = Review::create([
                            'submission_id' => $this->record->id,
                            'reviewer_id'   => $data['reviewer_id'],
                        ]);
                    }

                    if ($this->record->status === SubmissionTypes::PENDING_REVIEW->value) {
                        $this->record->update(['status' => SubmissionTypes::UNDER_REVIEW->value]);
                    }

                    $reviewerName = User::find($data['reviewer_id'])?->name;

                    Notification::make()
                        ->title('Reviewer Assigned')
                        ->body("Submission assigned to {$reviewerName}.")
                        ->success()->send();

                    $this->refreshFormData(['status']);
                }),

            Actions\EditAction::make(),
        ];
    }
}
