<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DistrictResource\Pages;
use App\Filament\Resources\DistrictResource\RelationManagers;
use App\Models\District;
use App\Services\Utility\Constants;
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
    protected static ?string $model = District::class;
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationGroup = 'System Configuration';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return Auth::user()?->isAdmin();
    }

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
                            ->rules([
                                'uppercase' // Enforces uppercase validation
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active Status'),
                    ])->columns(2),


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('students_count')
                    ->label('Students')
                    ->getStateUsing(fn($record) => $record->users()->where('role_id', Constants::STUDENT_ID)->count())
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('reviewers_count')
                    ->label('Reviewers')
                    ->getStateUsing(fn($record) => $record->users()->where('role_id', Constants::REVIEWER_ID)->count())
                    ->alignCenter()
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('robservers_count')
                    ->label('Observers')
                    ->getStateUsing(fn($record) => $record->users()->where('role_id', Constants::OBSERVER_ID)->count())
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->boolean()
                    ->trueLabel('Active Districts')
                    ->falseLabel('Inactive Districts')
                    ->native(false),

                Tables\Filters\Filter::make('has_users')
                    ->query(fn(Builder $query): Builder => $query->has('users'))
                    ->label('Has Users'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete District')
                    ->modalDescription('Are you sure you want to delete this district? This action cannot be undone and will affect all associated users.')
                    ->modalSubmitActionLabel('Yes, delete it')
                    ->before(function (District $record) {
                        // Prevent deletion if there are existing tasks
                        if ($record->users()->exists()) {
                            Notification::make()
                                ->title('Request Denied')
                                ->body('Cannot delete district with existing users. Please remove all users before deleting.')
                                ->danger()
                                ->send();
                            throw new Halt();
                        }
                        if ($record->churches()->exists()) {
                            Notification::make()
                                ->title('Request Denied')
                                ->body('Cannot delete district with existing church. Please remove all churches before deleting.')
                                ->danger()
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
                        ->before(function (Collection $records) {
                            $hasUsers = $records->contains(fn(District $record) => $record->users()->exists());
                            $hasChurches = $records->contains(fn(District $record) => $record->churches()->exists());

                            if ($hasUsers) {
                                Notification::make()
                                    ->title('Request Denied')
                                    ->body('Cannot delete districts with existing users. Please remove all users first.')
                                    ->danger()
                                    ->persistent()
                                    ->send();
                                throw new Halt();
                            }

                            if ($hasChurches) {
                                Notification::make()
                                    ->title('Request Denied')
                                    ->body('Cannot delete districts with existing churches. Please remove all churches first.')
                                    ->danger()
                                    ->persistent()
                                    ->send();
                                throw new Halt();
                            }
                        }),
                    // Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-m-check')
                        ->color('success')
                        ->action(fn($records) => $records->each(fn($record) => $record->update(['is_active' => true])))
                        ->requiresConfirmation()
                        ->modalHeading('Activate Districts')
                        ->modalDescription('Are you sure you want to activate the selected districts?'),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-m-x-mark')
                        ->color('danger')
                        ->action(fn($records) => $records->each(fn($record) => $record->update(['is_active' => false])))
                        ->requiresConfirmation()
                        ->modalHeading('Deactivate Districts')
                        ->modalDescription('Are you sure you want to deactivate the selected districts?'),
                ]),
            ]);
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
            'index' => Pages\ListDistricts::route('/'),
            'create' => Pages\CreateDistrict::route('/create'),
            'view' => Pages\ViewDistrict::route('/{record}'),
            'edit' => Pages\EditDistrict::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])->with(['users']);
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Code' => $record->code,
            'Users' => $record->users_count ?? 0,
        ];
    }
}
