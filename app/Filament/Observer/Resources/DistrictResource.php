<?php

namespace App\Filament\Observer\Resources;

use App\Filament\Observer\Resources\DistrictResource\Pages;
use App\Models\District;
use App\Services\Utility\Constants;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
        return Auth::user()?->isObserver();
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
            'view' => Pages\ViewDistrict::route('/{record}'),
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
