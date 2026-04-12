<?php

namespace App\Filament\Resources;

use App\Enums\RoleTypes;
use App\Filament\Resources\DistrictResource\Pages;
use App\Models\District;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class DistrictResource extends Resource
{
    protected static ?string $model          = District::class;
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationGroup = 'System Configuration';
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
                Section::make('District Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->unique(District::class, 'name', ignoreRecord: true),

                        Forms\Components\TextInput::make('code')
                            ->label('District Code')
                            ->required()
                            ->maxLength(10)
                            ->unique(District::class, 'code', ignoreRecord: true)
                            ->alphaNum()
                            ->rules(['uppercase'])
                            ->helperText('Uppercase alphanumeric, max 10 characters.'),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active'),
                    ])
                    ->columns(2),
            ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()->sortable()->weight('bold'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->badge()->color('primary'),

                // FIX: Previously used getStateUsing() with 3 separate role queries
                // per row — a classic N+1. Now uses withCount() via getEloquentQuery()
                // so all counts are loaded in a single query on page load.
                Tables\Columns\TextColumn::make('candidates_count')
                    ->label('Candidates')
                    ->alignCenter()
                    ->badge()->color('success'),

                Tables\Columns\TextColumn::make('reviewers_count')
                    ->label('Reviewers')
                    ->alignCenter()
                    ->badge()->color('warning'),

                Tables\Columns\TextColumn::make('observers_count')
                    ->label('Observers')
                    ->alignCenter()
                    ->badge()->color('info'),

                Tables\Columns\TextColumn::make('churches_count')
                    ->label('Churches')
                    ->alignCenter()
                    ->badge()->color('gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()->label('Active'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active')->falseLabel('Inactive')
                    ->native(false),
                Tables\Filters\Filter::make('has_users')
                    ->label('Has Members')
                    ->query(fn (Builder $q) => $q->has('users'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete District')
                    ->modalDescription('This will fail if the district has users or churches. Those must be removed first.')
                    ->before(function (District $record) {
                        if ($record->users()->exists()) {
                            Notification::make()->title('Denied')
                                ->body('Remove all users from this district before deleting.')
                                ->danger()->send();
                            throw new Halt();
                        }
                        if ($record->churches()->exists()) {
                            Notification::make()->title('Denied')
                                ->body('Remove all churches from this district before deleting.')
                                ->danger()->send();
                            throw new Halt();
                        }
                    }),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Collection $records) {
                            if ($records->contains(fn (District $r) => $r->users()->exists())) {
                                Notification::make()->title('Denied')
                                    ->body('One or more districts have users. Remove them first.')
                                    ->danger()->persistent()->send();
                                throw new Halt();
                            }
                            if ($records->contains(fn (District $r) => $r->churches()->exists())) {
                                Notification::make()->title('Denied')
                                    ->body('One or more districts have churches. Remove them first.')
                                    ->danger()->persistent()->send();
                                throw new Halt();
                            }
                        }),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-m-check')->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-m-x-mark')->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                ]),
            ]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDistricts::route('/'),
            'create' => Pages\CreateDistrict::route('/create'),
            'view'   => Pages\ViewDistrict::route('/{record}'),
            'edit'   => Pages\EditDistrict::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $candidateRoleId = Role::where('name', RoleTypes::STUDENT->value)->first()?->id;
        $reviewerRoleId  = Role::where('name', RoleTypes::REVIEWER->value)->first()?->id;
        $observerRoleId  = Role::where('name', RoleTypes::OBSERVER->value)->first()?->id;

        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->withCount([
                'churches',
                'users as candidates_count' => fn ($q) => $q->where('role_id', $candidateRoleId),
                'users as reviewers_count'  => fn ($q) => $q->where('role_id', $reviewerRoleId),
                'users as observers_count'  => fn ($q) => $q->where('role_id', $observerRoleId),
            ]);
    }
}
