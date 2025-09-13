<?php

namespace App\Filament\Student\Resources\ProfileResource\Pages;

use App\Models\User;
use App\Models\District;
use App\Models\Church;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfilePage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'My Profile';
    protected static ?string $navigationGroup = 'Account';
    protected static string $view = 'filament.student.pages.profile';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(Auth::user()->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(User::class, 'email', ignoreRecord: true),

                        Forms\Components\TextInput::make('phone')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(20),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Location Details')
                    ->schema([
                        Forms\Components\Select::make('district_id')
                            ->label('District')
                            ->options(District::pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('church_id', null);
                            }),

                        Forms\Components\Select::make('church_id')
                            ->label('Church')
                            ->options(function (Forms\Get $get) {
                                $districtId = $get('district_id');
                                if ($districtId) {
                                    return Church::where('district_id', $districtId)->pluck('name', 'id');
                                }
                                return [];
                            })
                            ->searchable()
                            ->required()
                            ->disabled(fn (Forms\Get $get) => !$get('district_id'))
                            ->helperText('Select a district first'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Change Password')
                    ->schema([
                        Forms\Components\TextInput::make('current_password')
                            ->label('Current Password')
                            ->password()
                            ->helperText('Enter your current password to change it'),

                        Forms\Components\TextInput::make('new_password')
                            ->label('New Password')
                            ->password()
                            ->minLength(8)
                            ->confirmed()
                            ->helperText('Leave blank if you don\'t want to change your password'),

                        Forms\Components\TextInput::make('new_password_confirmation')
                            ->label('Confirm New Password')
                            ->password(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Update Profile')
                ->submit('save')
                ->icon('heroicon-o-check')
                ->color('success'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();

        // Handle password change
        if (!empty($data['current_password']) && !empty($data['new_password'])) {
            if (!Hash::check($data['current_password'], $user->password)) {
                Notification::make()
                    ->title('Invalid Current Password')
                    ->body('The current password you entered is incorrect.')
                    ->danger()
                    ->send();
                return;
            }

            $data['password'] = Hash::make($data['new_password']);
            $data['password_updated_at'] = now();
        }

        // Remove password fields that shouldn't be saved
        unset($data['current_password'], $data['new_password'], $data['new_password_confirmation']);

        $user->update($data);

        Notification::make()
            ->title('Profile Updated')
            ->body('Your profile has been updated successfully.')
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->isStudent();
    }
}
