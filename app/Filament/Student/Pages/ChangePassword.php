<?php

namespace App\Filament\Student\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class ChangePassword extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon           = 'heroicon-o-key';
    protected static string  $view                     = 'filament.student.pages.force-change-password';
    protected static ?string $title                    = 'Change Password';
    protected static ?string $slug                     = 'change-password';
    protected static bool    $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('current_password')
                    ->label('Current Password')
                    ->password()
                    ->required()
                    ->revealable()
                    ->currentPassword(),

                TextInput::make('password')
                    ->label('New Password')
                    ->password()
                    ->required()
                    ->rule(
                        Password::default()
                            ->uncompromised()
                            ->min(8)
                            ->letters()
                            ->mixedCase()
                            ->numbers()
                            ->symbols()
                    )
                    ->different('current_password')
                    ->revealable()
                    ->validationMessages([
                        'uncompromised' => 'This password has been compromised. Please choose a different one.',
                    ]),

                TextInput::make('password_confirmation')
                    ->label('Confirm New Password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->same('password')
                    ->dehydrated(false),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('updatePassword')
                ->label('Update Password')
                ->submit('updatePassword'),
        ];
    }

    public function updatePassword(): void
    {
        $user = Auth::user();

        Log::info('ChangePassword: candidate initiated password change', [
            'event'      => 'candidate_password_change_attempt',
            'user_id'    => $user->id,
            'user_email' => $user->email,
            'is_first_change' => is_null($user->password_updated_at),
            'ip'         => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Validate form — this also checks currentPassword() rule
        $data = $this->form->getState();

        $user->update([
            'password'            => $data['password'],
            'password_updated_at' => now(),
        ]);

        Log::info('ChangePassword: candidate password updated successfully', [
            'event'      => 'candidate_password_change_success',
            'user_id'    => $user->id,
            'user_email' => $user->email,
            'ip'         => request()->ip(),
        ]);

        Notification::make()
            ->title('Password updated successfully!')
            ->body('Please log in again with your new password.')
            ->success()
            ->persistent()
            ->send();

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $this->redirect('/student/login');
    }

    public function getTitle(): string
    {
        return Auth::user()?->password_updated_at
            ? 'Change Password'
            : 'Change Default Password';
    }

    public function getHeading(): string
    {
        return '';
    }
}
