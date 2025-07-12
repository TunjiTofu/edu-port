<?php

namespace App\Filament\Resources;

use App\Enums\RoleTypes;
use App\Filament\Resources\ChangePasswordResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Actions\Action;
use Illuminate\Validation\Rules\Password;

class ChangePasswordResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Change Password';

    protected static ?string $modelLabel = 'User Password';

    protected static ?string $pluralModelLabel = 'User Passwords';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        // Allow admins to view all users, others can only view their own
        return Auth::user()?->isAdmin() || Auth::check();
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();

        // Admin can edit any user's password
        if ($user?->isAdmin()) {
            return true;
        }

        // Users can only edit their own password
        return $user?->id === $record->id;
    }

    public static function canCreate(): bool
    {
        // Only admins can create new users (this would be handled by UserResource)
        return Auth::user()?->isAdmin();
    }

    public static function canDelete($record): bool
    {
        // Password changes don't involve deletion
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Name')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('email')
                            ->label('Email Address')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('role')
                            ->label('Role')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(function ($record) {
                                if (!$record) return 'N/A';

                                if ($record->isAdmin()) return 'Administrator';
                                if ($record->isStudent()) return 'Student';
                                if ($record->isObserver()) return 'Observer';
                                if ($record->isReviewer()) return 'Reviewer';
                                return 'Unknown';
                            }),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Password Change')
                    ->schema([
                        Forms\Components\TextInput::make('current_password')
                            ->label('Current Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->visible(fn () => !Auth::user()?->isAdmin() || Auth::id() === request()->route('record'))
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        if (!Hash::check($value, Auth::user()->password)) {
                                            $fail('The current password is incorrect.');
                                        }
                                    };
                                },
                            ])
                            ->helperText('Enter your current password to confirm this change.'),

                        Forms\Components\TextInput::make('password')
                            ->label('New Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->rules([
                                Password::min(8)
                                    ->mixedCase()
                                    ->numbers()
                                    ->symbols()
                                    ->uncompromised(),
                            ])
                            ->helperText('Password must be at least 8 characters with uppercase, lowercase, numbers, and symbols.')
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('password_confirmation', '');
                            }),

                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Confirm New Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->same('password')
                            ->helperText('Re-enter the new password to confirm.'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Security Notice')
                    ->schema([
                        Forms\Components\Placeholder::make('security_notice')
                            ->content('
                                🔒 **Important Security Information:**
                                • Your password will be encrypted and stored securely
                                • You will be logged out from all devices after changing your password
                                • Make sure to use a strong, unique password
                            ')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();

                // If not admin, only show current user
                if (!$user?->isAdmin()) {
                    $query->where('id', $user->id);
                }

                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Full Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email Address')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->formatStateUsing(function ($record) {
                        if ($record->isAdmin()) return RoleTypes::ADMIN->value;
                        if ($record->isStudent()) return RoleTypes::STUDENT->value;
                        if ($record->isObserver()) return RoleTypes::OBSERVER->value;
                        if ($record->isReviewer()) return RoleTypes::REVIEWER->value;
                        return 'Unknown';
                    })
                    ->badge()
                    ->color(function($state) {
                        return match($state->name) {
                            RoleTypes::ADMIN->value => 'danger',
                            RoleTypes::STUDENT->value => 'info',
                            RoleTypes::OBSERVER->value => 'success',
                            RoleTypes::REVIEWER->value => 'primary',
                            default => 'gray',
                        };
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('password_updated_at')
                    ->label('Password Last Changed')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->placeholder('Never changed')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Account Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('User Role')
                    ->options([
                        'admin' => RoleTypes::ADMIN->value,
                        'student' => RoleTypes::STUDENT->value,
                        'observer' => RoleTypes::OBSERVER->value,
                        'reviewer' => RoleTypes::REVIEWER->value,
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!isset($data['value'])) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'admin' => $query->whereHas('role', fn($q) => $q->where('name', 'admin')),
                            'student' => $query->whereHas('role', fn($q) => $q->where('name', 'student')),
                            'observer' => $query->whereHas('role', fn($q) => $q->where('name', 'observer')),
                            'reviewer' => $query->whereHas('role', fn($q) => $q->where('name', 'reviewer')),
                            default => $query,
                        };
                    })
                    ->visible(fn () => Auth::user()?->isAdmin()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Change Password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->modalHeading(fn ($record) => "Change Password for {$record->name}")
                    ->modalDescription('Update the user\'s password. This action cannot be undone.')
                    ->modalSubmitActionLabel('Update Password')
                    ->successNotificationTitle('Password updated successfully')
                    ->after(function ($record) {
                        // Log the password change
                        activity()
                            ->performedOn($record)
                            ->causedBy(Auth::user())
                            ->log('Password changed');

                        // Send notification to the user
                        Notification::make()
                            ->title('Password Updated')
                            ->body('Your password has been successfully updated.')
                            ->success()
                            ->sendToDatabase($record);
                    }),
            ])
            ->bulkActions([
//                Tables\Actions\BulkActionGroup::make([
//                    Tables\Actions\DeleteBulkAction::make(),
//                ]),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChangePasswords::route('/'),
            'edit' => Pages\EditChangePassword::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        // Show count of users who haven't changed their password recently
        $thirtyDaysAgo = now()->subDays(30);
        $count = static::getModel()::where('password_updated_at', '<', $thirtyDaysAgo)
            ->orWhereNull('password_updated_at')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $thirtyDaysAgo = now()->subDays(30);
        $count = static::getModel()::where('password_updated_at', '<', $thirtyDaysAgo)
            ->orWhereNull('password_updated_at')
            ->count();

        return $count > 0 ? 'warning' : null;
    }
}
