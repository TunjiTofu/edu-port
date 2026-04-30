<?php

namespace App\Filament\Resources;

use App\Enums\RoleTypes;
use App\Filament\Resources\UserResource\Pages;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
                            ->label('Account Active')
                            ->default(true)
                            ->helperText('Inactive accounts cannot log in to any panel.'),
                    ])
                    ->columns(2),
            ]);
    }

    // ── Table — compact card layout ───────────────────────────────────────────
    //
    // FIX: The flat table had 12+ columns causing horizontal scroll on all
    // screen sizes. Replaced with a card stack layout that packs all the same
    // information into a single readable card per user — no horizontal scroll.

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([

                        // ── Avatar ─────────────────────────────────────────
                        // FIX: disk('public') causes Filament to generate a
                        // signed/routed URL internally, which fails silently.
                        // getStateUsing() feeds the pre-computed passport_photo_url
                        // accessor directly; disk(null) renders it as a raw URL
                        // with no further disk resolution — always works.
                        Tables\Columns\ImageColumn::make('avatar')
                            ->label('')
                            ->getStateUsing(fn ($record) => $record?->passport_photo_url)
                            ->disk(null)
                            ->circular()
                            ->size(52)
                            ->grow(false),

                        // ── Name + role badge + status badges ──────────────
                        Tables\Columns\Layout\Stack::make([

                            // Row 1: name + role
                            Tables\Columns\Layout\Split::make([
                                Tables\Columns\TextColumn::make('name')
                                    ->weight('bold')
                                    ->searchable()
                                    ->size(Tables\Columns\TextColumn\TextColumnSize::Medium),

                                Tables\Columns\TextColumn::make('role.name')
                                    ->badge()
                                    ->color(fn ($state) => match ($state) {
                                        RoleTypes::STUDENT->value  => 'primary',
                                        RoleTypes::REVIEWER->value => 'success',
                                        RoleTypes::OBSERVER->value => 'warning',
                                        RoleTypes::ADMIN->value    => 'danger',
                                        default                    => 'gray',
                                    })
                                    ->grow(false),
                            ]),

                            // Row 2: email + phone
                            Tables\Columns\TextColumn::make('email')
                                ->color('gray')
                                ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)
                                ->searchable()
                                ->icon('heroicon-m-envelope'),

                            Tables\Columns\TextColumn::make('phone')
                                ->color('gray')
                                ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)
                                ->placeholder('No phone')
                                ->icon('heroicon-m-phone'),

                            // Row 3: district → church
                            Tables\Columns\TextColumn::make('district_church')
                                ->label('')
                                ->getStateUsing(fn ($record) =>
                                    ($record->district?->name ?? '—') .
                                    ' › ' .
                                    ($record->church?->name ?? '—')
                                )
                                ->color('gray')
                                ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)
                                ->icon('heroicon-m-map-pin'),

                            // Row 4: mentor (candidates only)
                            Tables\Columns\TextColumn::make('mg_mentor')
                                ->label('Mentor')
                                ->color('gray')
                                ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)
                                ->placeholder('')
                                ->icon('heroicon-m-academic-cap')
                                ->visible(fn ($record) => $record?->isStudent() && ! empty($record?->mg_mentor)),

                            // Row 5: status badges
                            Tables\Columns\Layout\Split::make([
                                Tables\Columns\TextColumn::make('account_status')
                                    ->label('')
                                    ->getStateUsing(function ($record) {
                                        if (! $record)                           return '✅ Active';
                                        if ($record->isDisqualified())          return '🚫 Disqualified';
                                        if (! $record->is_active)               return '⏸ Inactive';
                                        if ($record->hasCompletedProgram())     return '🎓 Graduated';
                                        return '✅ Active';
                                    })
                                    ->badge()
                                    ->color(function ($record) {
                                        if (! $record)                          return 'info';
                                        if ($record->isDisqualified())          return 'danger';
                                        if (! $record->is_active)               return 'gray';
                                        if ($record->hasCompletedProgram())     return 'success';
                                        return 'info';
                                    }),

                                Tables\Columns\TextColumn::make('created_at')
                                    ->label('')
                                    ->since()
                                    ->color('gray')
                                    ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)
                                    ->grow(false),
                            ]),
                        ])->space(1)->grow(true),
                    ])->from('sm'),
                ])->space(2),
            ])
            ->contentGrid([
                'default' => 1,
                'md'      => 2,
                'xl'      => 3,
            ])
            ->filters([
                // ── Year filter — defaults to current year ─────────────────
                // "This year" shows:
                //   1. Users who registered this calendar year (new cohort)
                //   2. Users who registered in a previous year but are still
                //      active (program_completed_at IS NULL) — continuing candidates
                //
                // This matches the admin's mental model: "who is working this year?"
                // regardless of which program cohort they originally enrolled in.
                Tables\Filters\SelectFilter::make('year')
                    ->label('Program Year')
                    ->options(function () {
                        $currentYear = now()->year;
                        $years = \App\Models\User::selectRaw('YEAR(created_at) as yr')
                            ->distinct()->orderByDesc('yr')->pluck('yr')
                            ->mapWithKeys(fn ($y) => [$y => (string) $y])
                            ->toArray();
                        $years[$currentYear] = (string) $currentYear;
                        krsort($years);
                        return ['' => 'All Years'] + $years;
                    })
                    ->default((string) now()->year)
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        $year = $data['value'] ?? null;
                        if (! $year) return $query; // "All Years" selected

                        $year = (int) $year;
                        $currentYear = now()->year;

                        return $query->where(function ($q) use ($year, $currentYear) {
                            // Users who registered this year
                            $q->whereYear('users.created_at', $year);

                            // + active users from previous years (continuing cohort)
                            if ($year === $currentYear) {
                                $q->orWhere(function ($q2) use ($year) {
                                    $q2->whereYear('users.created_at', '<', $year)
                                        ->whereNull('program_completed_at')
                                        ->whereNull('disqualified_at');
                                });
                            }
                        });
                    }),

                TrashedFilter::make(),
                SelectFilter::make('role')->relationship('role', 'name'),
                SelectFilter::make('district')->relationship('district', 'name'),
                SelectFilter::make('church')->relationship('church', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active Status'),
                Tables\Filters\Filter::make('graduated')
                    ->label('Graduated Only')
                    ->query(fn ($q) => $q->whereNotNull('program_completed_at'))
                    ->toggle(),
                Tables\Filters\Filter::make('disqualified')
                    ->label('Disqualified Only')
                    ->query(fn ($q) => $q->whereNotNull('disqualified_at'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton(),
                Tables\Actions\EditAction::make()->iconButton(),

                // ── Graduate / Ungraduate ─────────────────────────────────
                Tables\Actions\Action::make('graduate')
                    ->label('Graduate')
                    ->icon('heroicon-o-academic-cap')
                    ->color('success')->iconButton()
                    ->tooltip('Mark as Graduated')
                    ->requiresConfirmation()
                    ->modalDescription('Candidate will be read-only — no submissions or profile edits.')
                    ->visible(fn (User $record) => $record->isStudent() && ! $record->hasCompletedProgram() && ! $record->isDisqualified())
                    ->action(function (User $record) {
                        $record->markProgramCompleted();
                        Log::info('Admin: graduated', ['admin_id' => Auth::id(), 'user_id' => $record->id]);
                        Notification::make()->title("{$record->name} marked as graduated.")->success()->send();
                    }),

                Tables\Actions\Action::make('ungraduate')
                    ->label('Undo Graduation')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')->iconButton()
                    ->tooltip('Undo Graduation')
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => $record->isStudent() && $record->hasCompletedProgram())
                    ->action(function (User $record) {
                        $record->unmarkProgramCompleted();
                        Notification::make()->title('Graduation reversed.')->warning()->send();
                    }),

                // ── Disqualify / Restore ──────────────────────────────────
                Tables\Actions\Action::make('disqualify')
                    ->label('Disqualify')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')->iconButton()
                    ->tooltip('Disqualify Candidate')
                    ->requiresConfirmation()
                    ->modalHeading('Disqualify Candidate?')
                    ->modalDescription('The candidate will be logged out and blocked from logging in until restored.')
                    ->form([
                        Forms\Components\Textarea::make('disqualification_reason')
                            ->label('Reason for Disqualification')
                            ->required()->rows(3)
                            ->placeholder('e.g. Failed to meet submission requirements by the deadline.'),
                    ])
                    ->visible(fn (User $record) => $record->isStudent() && ! $record->isDisqualified())
                    ->action(function (User $record, array $data) {
                        $record->disqualify($data['disqualification_reason']);
                        Log::warning('Admin: candidate disqualified', [
                            'event'    => 'admin_candidate_disqualified',
                            'admin_id' => Auth::id(),
                            'user_id'  => $record->id,
                            'reason'   => $data['disqualification_reason'],
                        ]);
                        Notification::make()->title("{$record->name} has been disqualified.")->danger()->send();
                    }),

                Tables\Actions\Action::make('restore_candidate')
                    ->label('Restore')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')->iconButton()
                    ->tooltip('Restore Disqualified Candidate')
                    ->requiresConfirmation()
                    ->modalHeading('Restore Candidate?')
                    ->modalDescription('This will re-activate the account. The candidate will be able to log in again.')
                    ->visible(fn (User $record) => $record->isStudent() && $record->isDisqualified())
                    ->action(function (User $record) {
                        $record->undisqualify();
                        Log::info('Admin: candidate restored', [
                            'event'    => 'admin_candidate_restored',
                            'admin_id' => Auth::id(),
                            'user_id'  => $record->id,
                        ]);
                        Notification::make()->title("{$record->name} has been restored.")->success()->send();
                    }),

                Tables\Actions\DeleteAction::make()->iconButton()
                    ->before(function (User $record) {
                        if ($record->role?->name === RoleTypes::ADMIN->value
                            && User::where('role_id', $record->role_id)->count() <= 1
                        ) {
                            Notification::make()->title('Denied')
                                ->body('Cannot delete the last admin.')->danger()->persistent()->send();
                            throw new Halt();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            $adminRole  = Role::where('name', RoleTypes::ADMIN->value)->first();
                            $adminCount = User::where('role_id', $adminRole?->id)->count();
                            $selected   = $records->where('role_id', $adminRole?->id)->count();
                            if ($adminCount - $selected < 1) {
                                Notification::make()->title('Denied')
                                    ->body('Cannot delete all admin users.')
                                    ->danger()->persistent()->send();
                                throw new Halt();
                            }
                        }),

                    Tables\Actions\BulkAction::make('bulk_graduate')
                        ->label('Mark as Graduated')
                        ->icon('heroicon-o-academic-cap')->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $r) {
                                if ($r->isStudent() && ! $r->hasCompletedProgram()) {
                                    $r->markProgramCompleted(); $count++;
                                }
                            }
                            Notification::make()->title("{$count} candidate(s) graduated.")->success()->send();
                        }),

                    Tables\Actions\BulkAction::make('bulk_activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-m-check')->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),

                    Tables\Actions\BulkAction::make('bulk_deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-m-x-mark')->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),

                    Tables\Actions\RestoreBulkAction::make(),
                ]),
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
            ->with(['district', 'church', 'role'])
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
