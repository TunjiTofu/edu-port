<?php

namespace App\Filament\Student\Resources\ProfileResource\Pages;

use App\Filament\Student\Resources\ProfileResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EditProfile extends EditRecord
{
    protected static string $resource = ProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to Profile')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => static::getResource()::getUrl('index')),
        ];
    }

    public function getTitle(): string
    {
        return 'Edit My Profile';
    }

    protected function beforeSave(): void
    {
        $user = Auth::user();

        Log::info('Profile: candidate saving profile update', [
            'event'      => 'candidate_profile_update_attempt',
            'user_id'    => $user->id,
            'user_email' => $user->email,
            'ip'         => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    protected function afterSave(): void
    {
        $user = Auth::user();

        Log::info('Profile: candidate profile updated successfully', [
            'event'      => 'candidate_profile_update_success',
            'user_id'    => $user->id,
            'user_email' => $user->email,
        ]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Profile Updated')
            ->body('Your profile has been updated successfully.');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
