<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RubricResource\Pages;
use App\Models\Rubric;
use App\Models\Task;
use Filament\Forms;
use Filament\Forms\Components\Section as FormSection;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class RubricResource extends Resource
{
    protected static ?string $model = Rubric::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
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
                FormSection::make('Rubric Information')
                    ->schema([
                        Forms\Components\Select::make('task_id')
                            ->label('Task')
                            ->relationship('task', 'title')
                            ->relationship(
                                name: 'task',
                                titleAttribute: 'title',
                                modifyQueryUsing: fn(Builder $query) => $query->where('is_active', true)->with('section')
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $nextOrder = Rubric::where('task_id', $state)->max('order_index') + 1;
                                    $set('order_index', $nextOrder);
                                }
                            })
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->section->name} - {$record->title}"),

                        Forms\Components\TextInput::make('title')
                            ->label('Rubric Title')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Brief title for this rubric criteria'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Detailed description of what this criteria evaluates'),
                    ])->columns(2),

                FormSection::make('Scoring & Settings')
                    ->schema([
                        Forms\Components\TextInput::make('max_points')
                            ->label('Maximum Points')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->required()
                            ->helperText('Maximum points that can be awarded for this criteria'),

                        Forms\Components\TextInput::make('order_index')
                            ->label('Display Order')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->helperText('Order in which this rubric appears during evaluation'),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active Status')
                            ->helperText('Only active rubrics will be used for evaluation'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('task.section.name')
                    ->label('Section')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('task.title')
                    ->label('Task')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Rubric Title')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('max_points')
                    ->label('Max Points')
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('order_index')
                    ->label('Order')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('secondary'),

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

                SelectFilter::make('task')
                    ->relationship('task', 'title')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('section')
                    ->label('Section')
                    ->options(function () {
                        return \App\Models\Section::pluck('name', 'id')->toArray();
                    })
                    ->query(function (Builder $query, $data) {
                        if ($data['value']) {
                            $query->whereHas('task.section', function ($q) use ($data) {
                                $q->where('id', $data['value']);
                            });
                        }
                    }),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Rubric $record) {
                        // Prevent deletion if there are existing review rubrics
                        if ($record->reviewRubrics()->exists()) {
                            Notification::make()
                                ->title('Request Denied')
                                ->body('Cannot delete rubric with existing reviews. Please remove all reviews before deleting.')
                                ->danger()
                                ->persistent()
                                ->send();
                            throw new Halt();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('order_index');
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
            'index' => Pages\ListRubrics::route('/'),
            'create' => Pages\CreateRubric::route('/create'),
//            'view' => Pages\ViewRubric::route('/{record}'),
            'edit' => Pages\EditRubric::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['task.section'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
