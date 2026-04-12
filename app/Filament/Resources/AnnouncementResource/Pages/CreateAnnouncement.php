<?php

namespace App\Filament\Resources\AnnouncementResource\Pages;

use App\Filament\Resources\AnnouncementResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;

    public function getTitle(): string
    {
        return 'New Announcement / Broadcast';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by']   = Auth::id();
        $data['published_at'] = $data['is_published'] ? now() : null;

        // Remove the virtual send_email / send_sms checkboxes before saving
        // (they are not real columns — we handle them in afterCreate)
        $this->sendEmail = (bool) ($data['send_email'] ?? false);
        $this->sendSms   = (bool) ($data['send_sms']   ?? false);

        unset($data['send_email'], $data['send_sms']);

        return $data;
    }

    // Store send preferences between mutateFormDataBeforeCreate and afterCreate
    private bool $sendEmail = false;
    private bool $sendSms   = false;

    protected function afterCreate(): void
    {
        if ($this->sendEmail || $this->sendSms) {
            AnnouncementResource::dispatchBroadcast(
                $this->record,
                $this->sendEmail,
                $this->sendSms
            );
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
