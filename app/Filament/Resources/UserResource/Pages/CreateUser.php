<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Admin-created accounts don't need to change their password on first login
        // since the admin sets it — mark it as already changed.
        $data['password_updated_at'] = now();
        $data['is_active']           = $data['is_active'] ?? true;

        return $data;
    }

    protected function afterCreate(): void
    {
        Log::info('Admin: user account created', [
            'event'      => 'admin_user_created',
            'admin_id'   => Auth::id(),
            'new_user_id'=> $this->record->id,
            'email'      => $this->record->email,
            'role'       => $this->record->role?->name,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
