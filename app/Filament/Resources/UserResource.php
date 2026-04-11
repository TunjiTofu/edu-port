<?php

namespace App\Filament\Resources;

use App\Enums\RoleTypes;
use App\Filament\Resources\UserResource\Pages;
use App\Models\Church;
use App\Models\District;
use App\Models\Role;
use App\Models\User;
use App\Services\TermiiService;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UserResource extends Resource
{
    protected static ?string $model          = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?int    $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return Auth::user()?->isAdmin();
    }

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->email()->required()
                            ->unique(User::class, 'email', ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->tel()->maxLength(20),

                        Forms\Components\TextInput::make('mg_mentor')
                            ->label('MG Mentor')
                            ->maxLength(255)
                            ->helperText('Full name of the candidate\'s MG mentor.'),
                    ])
                    ->columns(2),

                Section::make('Role & Assignment')
                    ->schema([
                        Forms\Components\Select::make('role_id')
                            ->label('Role')
                            ->options(Role::all()->pluck('display_name', 'id'))
                            ->required()->searchable()->preload(),

                        Forms\Components\Select::make('district_id')
                            ->label('District')
                            ->options(District::all()->pluck('name', 'id'))
                            ->required()->searchable()->preload()
                            ->live()
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('church_id', null)),

                        Forms\Components\Select::make('church_id')
                            ->label('Church')
                            ->options(fn (callable $get) =>
                            $get('district_id')
                                ? Church::where('district_id', $get('district_id'))->pluck('name', 'id')
                                : []
                            )
                            ->placeholder(fn (callable $get) =>
                            $get('district_id') ? 'Select a church' : 'Select a district first'
                            )
                            ->required()->searchable()
                            ->disabled(fn (callable $get) => ! $get('district_id')),
                    ])
                    ->columns(2),

                Section::make('Security & Status')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->revealable(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Status')->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('passport_photo')
                    ->label('')
                    ->disk(config('filesystems.default') === 's3' ? 's3' : 'public')
                    ->circular()->size(36)
                    ->defaultImageUrl(asset('images/default-avatar.png')),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()->sortable()->toggleable(),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable()->toggleable(),

                Tables\Columns\TextColumn::make('mg_mentor')
                    ->label('Mentor')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('role.name')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        RoleTypes::STUDENT->value  => 'primary',
                        RoleTypes::REVIEWER->value => 'success',
                        RoleTypes::OBSERVER->value => 'warning',
                        RoleTypes::ADMIN->value    => 'danger',
                        default                    => 'gray',
                    }),

                Tables\Columns\TextColumn::make('district.name')
                    ->label('District')->sortable()->toggleable(),

                Tables\Columns\TextColumn::make('church.name')
                    ->label('Church')->sortable()->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()->label('Active'),

                Tables\Columns\TextColumn::make('program_completed_at')
                    ->label('Graduated')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn ($state) => $state ? '🎓 Graduated' : null)
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),

                SelectFilter::make('role')->relationship('role', 'name'),
                SelectFilter::make('district')->relationship('district', 'name'),
                SelectFilter::make('church')->relationship('church', 'name'),

                Tables\Filters\TernaryFilter::make('is_active')->label('Active Status'),

                Tables\Filters\TernaryFilter::make('graduated')
                    ->label('Graduated')
                    ->nullable()
                    ->attribute('program_completed_at'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                // ── Graduate / Ungraduate ─────────────────────────────────
                Tables\Actions\Action::make('graduate')
                    ->label('Mark as Graduated')
                    ->icon('heroicon-o-academic-cap')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark Candidate as Graduated?')
                    ->modalDescription('This will lock the candidate from making new submissions, editing their profile, or enrolling in programs. They can still view their results and announcements.')
                    ->modalSubmitActionLabel('Yes, Mark as Graduated')
                    ->visible(fn (User $record) =>
                        $record->isStudent() && ! $record->hasCompletedProgram()
                    )
                    ->action(function (User $record) {
                        $record->markProgramCompleted();

                        Log::info('Admin: candidate marked as graduated', [
                            'event'        => 'admin_candidate_graduated',
                            'admin_id'     => Auth::id(),
                            'candidate_id' => $record->id,
                            'email'        => $record->email,
                        ]);

                        Notification::make()
                            ->title('Candidate Graduated')
                            ->body("{$record->name} has been marked as having completed the program.")
                            ->success()->send();
                    }),

                Tables\Actions\Action::make('ungraduate')
                    ->label('Undo Graduation')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Undo Graduation?')
                    ->modalDescription('This will restore full access for this candidate. They will be able to submit, edit their profile, and enroll in programs again.')
                    ->modalSubmitActionLabel('Yes, Restore Access')
                    ->visible(fn (User $record) =>
                        $record->isStudent() && $record->hasCompletedProgram()
                    )
                    ->action(function (User $record) {
                        $record->unmarkProgramCompleted();

                        Log::info('Admin: candidate graduation reversed', [
                            'event'        => 'admin_candidate_ungraduated',
                            'admin_id'     => Auth::id(),
                            'candidate_id' => $record->id,
                        ]);

                        Notification::make()
                            ->title('Graduation Reversed')
                            ->body("{$record->name} can now submit and access the portal again.")
                            ->warning()->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->before(function (User $record) {
                        if ($record->role?->name === RoleTypes::ADMIN->value
                            && User::where('role_id', $record->role_id)->count() <= 1
                        ) {
                            Notification::make()
                                ->title('Request Denied')
                                ->body('You cannot delete the last admin user.')
                                ->danger()->persistent()->send();
                            throw new Halt();
                        }
                    }),

                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            $adminRoleId  = Role::where('name', RoleTypes::ADMIN->value)->first()?->id;
                            $adminCount   = User::where('role_id', $adminRoleId)->count();
                            $adminSelected = $records->where('role_id', $adminRoleId)->count();

                            if ($adminCount - $adminSelected < 1) {
                                Notification::make()
                                    ->title('Request Denied')
                                    ->body('You cannot delete all admin users.')
                                    ->danger()->persistent()->send();
                                throw new Halt();
                            }
                        }),

                    // ── Bulk Graduate ──────────────────────────────────────
                    Tables\Actions\BulkAction::make('bulk_graduate')
                        ->label('Mark as Graduated')
                        ->icon('heroicon-o-academic-cap')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Graduate Selected Candidates?')
                        ->modalDescription('All selected candidates will be locked to read-only mode.')
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->isStudent() && ! $record->hasCompletedProgram()) {
                                    $record->markProgramCompleted();
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->title("{$count} candidate(s) marked as graduated.")
                                ->success()->send();
                        }),
                ]),
                Tables\Actions\RestoreBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view'   => Pages\ViewUser::route('/{record}'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['district', 'church'])
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
