<?php

namespace App\Filament\Student\Resources\ChangePasswordResource\Pages;

use App\Filament\Resources\ChangePasswordResource;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class EditChangePassword extends EditRecord
{
    protected static string $resource = ChangePasswordResource::class;

    public function getTitle(): string
    {
        return 'Change My Password';
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Update Password')
                ->icon('heroicon-o-check')
                ->color('success'),
            $this->getCancelFormAction()
                ->label('Cancel')
                ->color('gray'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Remove fields that shouldn't be saved
        unset($data['current_password']);
        unset($data['password_confirmation']);

        // Hash the new password
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
            $data['password_updated_at'] = now();
        }

        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Password Updated')
            ->body('The password has been successfully updated.')
            ->actions([
                Action::make('ok')
                    ->label('OK')
                    ->close(),
            ]);
    }

    protected function afterSave(): void
    {
//        // Log the password change activity
//        activity()
//            ->performedOn($this->record)
//            ->causedBy(Auth::user())
//            ->withProperties([
//                'user_id' => $this->record->id,
//                'changed_by' => Auth::id(),
//                'ip_address' => request()->ip(),
//                'user_agent' => request()->userAgent(),
//            ])
//            ->log('Password changed');

        // If user changed their own password, redirect to dashboard
        if (Auth::id() === $this->record->id) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.student.auth.login');
    }
}

