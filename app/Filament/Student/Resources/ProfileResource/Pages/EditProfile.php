<?php

namespace App\Filament\Student\Resources\ProfileResource\Pages;

use App\Filament\Student\Resources\ProfileResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Actions\Action as NotificationAction;
use Illuminate\Validation\ValidationException;

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
                ->url(fn() => static::getResource()::getUrl('index')),
        ];
    }

    public function getTitle(): string
    {
        return 'Edit Profile';
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

    protected function onValidationError(ValidationException $exception): void
    {
        // Handle validation errors
        Notification::make()
            ->title('Validation Error')
            ->body('Please check the form for errors.')
            ->danger()
            ->send();
    }
}
