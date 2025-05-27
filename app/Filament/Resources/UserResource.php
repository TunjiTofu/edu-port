<?php

namespace App\Filament\Resources;

use App\Enums\RoleTypes;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\Church;
use App\Models\District;
use App\Models\Role;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return Auth::user()?->isAdmin();
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(User::class, 'email', ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Role & Assignment')
                    ->schema([
                        Forms\Components\Select::make('role_id')
                            ->label('Role')
                            ->options(Role::all()->pluck('display_name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('district_id')
                            ->label('District')
                            ->options(District::all()->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->placeholder('Choose a district')
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('church_id', null); // Clear church selection when district changes
                            }),

                        Forms\Components\Select::make('church_id')
                            ->label('Church')
                            ->options(function (callable $get) {
                                $districtId = $get('district_id');
                                if (!$districtId) {
                                    return [];
                                }
                                return Church::where('district_id', $districtId)->pluck('name', 'id');
                            })
                            ->placeholder(function (callable $get) {
                                return $get('district_id') ? 'Select a church' : 'Select a district first';
                            })
                            ->required()
                            ->searchable()
                            ->disabled(fn(callable $get) => !$get('district_id'))
                            ->helperText(function (callable $get) {
                                $districtId = $get('district_id');
                                if ($districtId && Church::where('district_id', $districtId)->doesntExist()) {
                                    return 'No churches available for this district';
                                }
                                return null;
                            }),
                    ])
                    ->columns(2),

                Section::make('Security & Status')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required(fn(string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->dehydrated(fn($state) => filled($state))
                            ->dehydrateStateUsing(fn($state) => Hash::make($state))
                            ->revealable(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Status')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('role.name')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'student' => 'primary',
                        'reviewer' => 'success',
                        'observer' => 'warning',
                        'admin' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('district.name')
                    ->label('District')
                    ->sortable(),

                Tables\Columns\TextColumn::make('church.name')
                    ->label('Church')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('role')
                    ->relationship('role', 'name'),

                SelectFilter::make('district')
                    ->relationship('district', 'name'),

                SelectFilter::make('church')
                    ->relationship('church', 'name'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            // ->actions([
            //     Tables\Actions\EditAction::make(),
            // ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (User $record) {
                        // Prevent deletion of last admin
                        if ($record->role->name === RoleTypes::ADMIN->value && User::where('role_id', 1)->count() <= 1) {
                            Notification::make()
                                ->title('Request Denied')
                                ->body('You cannot delete the last admin user.')
                                ->danger()
                                ->persistent()
                                ->send();
                            throw new Halt();
                        }
                    }),
                Tables\Actions\ForceDeleteAction::make(), // Permanent delete
                Tables\Actions\RestoreAction::make(), // Restore soft-deleted
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            $adminCount = User::where('role_id', 1)->count();
                            $adminRecords = $records->where('role_id', '1')->count();
                            // Prevent deletion of all admins
                            if ($adminCount - $adminRecords < 1) {
                                Notification::make()
                                    ->title('Request Denied')
                                    ->body('You cannot delete all admin users.')
                                    ->danger()
                                    ->persistent()
                                    ->send();
                                throw new Halt();
                            }
                        }),
                ]),
                // Tables\Actions\ForceDeleteBulkAction::make()
                //     ->before(function ($records) {
                //         $adminCount = User::where('role_id', 1)->count();
                //         $adminRecords = $records->where('role_id', '1')->count();
                //         // Prevent deletion of all admins
                //         if ($adminCount - $adminRecords < 1) {
                //             Notification::make()
                //                 ->title('Request Denied')
                //                 ->body('You cannot delete all admin users.')
                //                 ->danger()
                //                 ->persistent()
                //                 ->send();
                //             throw new Halt();
                //         }
                //     }),
                Tables\Actions\RestoreBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['district', 'church'])->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }
}
