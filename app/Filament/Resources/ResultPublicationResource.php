<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResultPublicationResource\Pages;
use App\Models\ResultPublication;
use App\Models\Task;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\Actions\Action;
use Filament\Support\Enums\ActionSize;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ResultPublicationResource extends Resource
{
    protected static ?string $model = ResultPublication::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'Result Publications';

    protected static ?string $modelLabel = 'Result Publication';

    protected static ?string $pluralModelLabel = 'Result Publications';

    protected static ?string $navigationGroup = 'Academic Management';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return Auth::user()?->isAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('task.title')
                    ->label('Task')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Published Date')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->placeholder('Not published')
                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('publisher.name')
                    ->label('Published By')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Not assigned')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_published')
                    ->label('Publication Status')
                    ->placeholder('All publications')
                    ->trueLabel('Published only')
                    ->falseLabel('Unpublished only'),

                SelectFilter::make('published_by')
                    ->label('Published By')
                    ->relationship('publisher', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('task_id')
                    ->label('Task')
                    ->relationship('task', 'title')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('toggle_publish')
                    ->label(fn (ResultPublication $record) =>
                    $record->is_published ? 'Unpublish' : 'Publish'
                    )
                    ->icon(fn (ResultPublication $record) =>
                    $record->is_published ? 'heroicon-o-eye-slash' : 'heroicon-o-eye'
                    )
                    ->color(fn (ResultPublication $record) =>
                    $record->is_published ? 'warning' : 'success'
                    )
                    ->requiresConfirmation()
                    ->modalHeading(fn (ResultPublication $record) =>
                    $record->is_published ? 'Unpublish Result' : 'Publish Result'
                    )
                    ->modalDescription(fn (ResultPublication $record) =>
                    $record->is_published
                        ? 'Are you sure you want to unpublish this result? Students will no longer be able to view it.'
                        : 'Are you sure you want to publish this result? Students will be able to view it immediately.'
                    )
                    ->action(function (ResultPublication $record) {
                        if ($record->is_published) {
                            $record->update([
                                'is_published' => false,
                                'published_at' => null,
                                'published_by' => null,
                            ]);

                            Notification::make()
                                ->title('Result unpublished successfully')
                                ->success()
                                ->send();
                        } else {
                            $record->update([
                                'is_published' => true,
                                'published_at' => now(),
                                'published_by' => auth()->id(),
                            ]);

                            Notification::make()
                                ->title('Result published successfully')
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('publish_selected')
                        ->label('Publish Selected')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Publish Selected Results')
                        ->modalDescription('Are you sure you want to publish the selected results? Students will be able to view them immediately.')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'is_published' => true,
                                    'published_at' => now(),
                                    'published_by' => auth()->id(),
                                ]);
                            });

                            Notification::make()
                                ->title('Selected results published successfully')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('unpublish_selected')
                        ->label('Unpublish Selected')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Unpublish Selected Results')
                        ->modalDescription('Are you sure you want to unpublish the selected results? Students will no longer be able to view them.')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'is_published' => false,
                                    'published_at' => null,
                                    'published_by' => null,
                                ]);
                            });

                            Notification::make()
                                ->title('Selected results unpublished successfully')
                                ->success()
                                ->send();
                        }),
                ]),
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
            'index' => Pages\ListResultPublications::route('/'),
            'create' => Pages\CreateResultPublication::route('/create'),
            'edit' => Pages\EditResultPublication::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_published', false)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::where('is_published', false)->count() > 0 ? 'warning' : null;
    }
}
