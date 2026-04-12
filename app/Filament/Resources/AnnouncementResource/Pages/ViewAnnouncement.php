<?php

// ─── ViewAnnouncement.php ─────────────────────────────────────────────────────
// Path: app/Filament/Resources/AnnouncementResource/Pages/ViewAnnouncement.php

namespace App\Filament\Resources\AnnouncementResource\Pages;

use App\Filament\Resources\AnnouncementResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAnnouncement extends ViewRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('send_now')
                ->label('Resend Broadcast')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->form([
                    \Filament\Forms\Components\Checkbox::make('via_email')
                        ->label('Send via Email')->default(true),
                    \Filament\Forms\Components\Checkbox::make('via_sms')
                        ->label('Send via SMS')->default(false),
                ])
                ->action(fn (array $data) =>
                AnnouncementResource::dispatchBroadcast(
                    $this->record,
                    $data['via_email'],
                    $data['via_sms']
                )
                ),
        ];
    }
}
