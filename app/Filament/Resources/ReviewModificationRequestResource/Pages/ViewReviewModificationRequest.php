<?php

namespace App\Filament\Resources\ReviewModificationRequestResource\Pages;

use App\Filament\Resources\ReviewModificationRequestResource;
use App\Models\ReviewModificationRequest;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms;
use Filament\Notifications\Notification;

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
                ->visible(fn (): bool => $this->record->isPending())
                ->form([
                    Forms\Components\Textarea::make('admin_comments')
                        ->label('Comments (Optional)')
                        ->placeholder('Add any comments about this approval...')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $this->record->approve(
                        admin: auth()->user(),
                        comments: $data['admin_comments'] ?? null
                    );

                    Notification::make()
                        ->title('Request Approved')
                        ->body('The modification request has been approved successfully.')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'status',
                        'admin_id',
                        'admin_comments',
                        'approved_at'
                    ]);
                })
                ->requiresConfirmation()
                ->modalHeading('Approve Modification Request')
                ->modalDescription('Are you sure you want to approve this modification request?'),

            Actions\Action::make('reject')
                ->label('Reject Request')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->record->isPending())
                ->form([
                    Forms\Components\Textarea::make('admin_comments')
                        ->label('Reason for Rejection')
                        ->placeholder('Please provide a reason for rejecting this request...')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $this->record->reject(
                        admin: auth()->user(),
                        comments: $data['admin_comments']
                    );

                    Notification::make()
                        ->title('Request Rejected')
                        ->body('The modification request has been rejected.')
                        ->danger()
                        ->send();

                    $this->refreshFormData([
                        'status',
                        'admin_id',
                        'admin_comments'
                    ]);
                })
                ->requiresConfirmation()
                ->modalHeading('Reject Modification Request')
                ->modalDescription('Are you sure you want to reject this modification request?'),
//
//            Actions\Action::make('view_review')
//                ->label('View Related Review')
//                ->icon('heroicon-o-eye')
//                ->color('gray')
////                ->url(fn (): string => route('filament.admin.resources.reviews.view', ['record' => $this->record->review_id]))
//                ->openUrlInNewTab(),
        ];
    }
}
