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
                Forms\Components\Section::make('Result Publication Details')
                    ->schema([
                        Forms\Components\Select::make('task_id')
                            ->label('Task')
                            ->relationship('task', 'title')
                            ->searchable()
                            ->preload()
                            ->required()
//                            ->createOptionForm([
//                                Forms\Components\TextInput::make('title')
//                                    ->required()
//                                    ->maxLength(255),
//                                Forms\Components\Textarea::make('description')
//                                    ->maxLength(65535)
//                                    ->columnSpanFull(),
//                            ])
                            ->helperText('Select the task for which you want to create a result publication.')
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Check if a result publication already exists for this task
                                if ($state) {
                                    $existingPublication = ResultPublication::where('task_id', $state)->first();
                                    if ($existingPublication) {
                                        Notification::make()
                                            ->title('Warning')
                                            ->body('A result publication already exists for this task.')
                                            ->warning()
                                            ->send();
                                    }
                                }
                            }),

                        Forms\Components\Toggle::make('is_published')
                            ->label('Publish Immediately')
                            ->helperText('Toggle this to publish the result immediately upon creation.')
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $set('published_at', now());
                                    $set('published_by', auth()->id());
                                } else {
                                    $set('published_at', null);
                                    $set('published_by', null);
                                }
                            }),

                        Forms\Components\Hidden::make('published_at')
                            ->default(fn (Forms\Get $get) => $get('is_published') ? now() : null),

                        Forms\Components\Hidden::make('published_by')
                            ->default(fn (Forms\Get $get) => $get('is_published') ? auth()->id() : null),
                    ])
                    ->columnSpan(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Placeholder::make('task_info')
                            ->label('Task Information')
                            ->content(function (Forms\Get $get) {
                                if (!$get('task_id')) {
                                    return 'Select a task to view its details.';
                                }

                                $task = Task::find($get('task_id'));
                                if (!$task) {
                                    return 'Task not found.';
                                }

                                return "Title: {$task->title}\nDescription: " . str($task->description ?? 'No description')->limit(100);
                            })
                            ->visible(fn (Forms\Get $get) => $get('task_id')),

                        Forms\Components\Placeholder::make('publication_status')
                            ->label('Publication Status')
                            ->content(function (Forms\Get $get) {
                                if ($get('is_published')) {
                                    return '✅ This result will be published and visible to students.';
                                } else {
                                    return '⏳ This result will be saved as draft and not visible to students.';
                                }
                            }),
                    ])
                    ->columnSpan(1),
            ])
            ->columns(3);
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
