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
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ChangePassword extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static string $view = 'filament.student.pages.force-change-password';
    protected static ?string $title = 'Change Password';
    protected static ?string $slug = 'change-password';
    protected static bool $shouldRegisterNavigation = false;

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
                    ->rule(Password::default()
                        ->uncompromised() // This checks against common/pwned passwords
                        ->min(8)
                        ->letters()
                        ->mixedCase()
                        ->numbers()
                        ->symbols()
                    )
                    ->different('current_password')
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->revealable()
                    ->validationMessages([
                        'uncompromised' => 'The password you entered is too common or has been compromised. Please choose a different password.',
                    ]),

                TextInput::make('password_confirmation')
                    ->label('Confirm New Password')
                    ->password()
                    ->required()
                    ->revealable()
                    ->same('password')
                    ->dehydrated(false),
            ])
            ->statePath('data');
    }

    public function updatePassword(): void
    {
        $data = $this->form->getState();

        $user = Auth::user();
        $user->update([
            'password' => $data['password'],
            'password_updated_at' => now(),
        ]);

        Notification::make()
            ->title('Password updated successfully!')
            ->body('You will be redirected to login with your new password.')
            ->success()
            ->persistent() // Make notification stay longer
            ->send();

        // Add JavaScript to redirect after showing a notification
        $this->dispatch('redirect-after-delay', url: '/student/login');
    }

    public function getTitle(): string
    {
        $user = Auth::user();

        if (!$user->password_updated_at) {
            return 'Change Default Password';
        }

        return 'Change Password';
    }

//    public function getHeading(): string
//    {
//        $user = Auth::user();
//
//        if (!$user->password_updated_at) {
//            return 'You must change your default password before continuing';
//        }
//
//        return 'Change Password';
//    }

    public function getHeading(): string
    {
        return '';
    }

    public function getHeader(): ?View
    {
        return null;
    }
}
