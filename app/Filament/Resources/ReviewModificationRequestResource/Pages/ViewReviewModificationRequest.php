<?php

namespace App\Filament\Resources\ReviewModificationRequestResource\Pages;

use App\Enums\ReviewModificationStatus;
use App\Filament\Resources\ReviewModificationRequestResource;
use App\Models\ReviewModificationRequest;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewReviewModificationRequest extends ViewRecord
{
    protected static string $resource = ReviewModificationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Approve Request')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve this modification request?')
                ->modalDescription('The reviewer will be granted one-time access to update their review.')
                ->form([
                    Forms\Components\Textarea::make('admin_comments')
                        ->label('Comments (optional)')->rows(2),
                ])
                ->visible(fn () => $this->record->isPending())
                ->action(function (array $data) {
                    $this->record->approve(Auth::user(), $data['admin_comments'] ?? null);
                    Notification::make()->title('Request Approved')->success()->send();
                    $this->refreshFormData(['status', 'admin_id', 'admin_comments', 'approved_at']);
                }),

            Actions\Action::make('reject')
                ->label('Reject Request')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    Forms\Components\Textarea::make('admin_comments')
                        ->label('Reason for rejection')->required()->rows(2),
                ])
                ->visible(fn () => $this->record->isPending())
                ->action(function (array $data) {
                    $this->record->reject(Auth::user(), $data['admin_comments']);
                    Notification::make()->title('Request Rejected')->warning()->send();
                    $this->refreshFormData(['status', 'admin_id', 'admin_comments']);
                }),
        ];
    }
}
