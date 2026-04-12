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
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => static::getResource()::getUrl('index')),
        ];
    }

    public function getTitle(): string { return 'My Profile'; }

    protected function beforeSave(): void
    {
        Log::info('Profile: candidate saving update', [
            'event'   => 'candidate_profile_update_attempt',
            'user_id' => Auth::id(),
            'ip'      => request()->ip(),
        ]);
    }

    protected function afterSave(): void
    {
        $user = $this->record->fresh();

        // Mark profile complete if all required fields are now filled
        $user->markProfileComplete();

        Log::info('Profile: candidate profile saved', [
            'event'            => 'candidate_profile_update_success',
            'user_id'          => $user->id,
            'profile_complete' => $user->isProfileComplete(),
        ]);

        // Notify if still incomplete
        if (! $user->isProfileComplete()) {
            $missing = collect([
                'phone'          => empty($user->phone),
                'church'         => is_null($user->church_id),
                'passport photo' => empty($user->passport_photo),
            ])->filter()->keys()->implode(', ');

            Notification::make()
                ->warning()
                ->title('Profile Incomplete')
                ->body("Still missing: {$missing}. Complete these to access the full portal.")
                ->persistent()
                ->send();
        }
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Profile Saved')
            ->body('Your profile has been updated successfully.');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
