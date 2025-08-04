<?php

namespace App\Filament\Resources\TaskResource\RelationManagers;

use App\Models\Rubric;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RubricsRelationManager extends RelationManager
{
    protected static string $relationship = 'rubrics';
    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Criteria Title')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('max_points')
                            ->label('Maximum Points')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->required(),
                    ]),

                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->columnSpanFull()
                    ->helperText('Detailed description of what this criteria evaluates'),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('order_index')
                            ->label('Display Order')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(function () {
                                $taskId = $this->getOwnerRecord()->id;
                                return Rubric::where('task_id', $taskId)->max('order_index') + 1;
                            }),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active Status'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Criteria Title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->wrap()
                    ->toggleable(),

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
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['task_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Rubric $record) {
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
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                if ($record->reviewRubrics()->exists()) {
                                    Notification::make()
                                        ->title('Request Denied')
                                        ->body("Cannot delete rubric '{$record->title}' with existing reviews.")
                                        ->danger()
                                        ->persistent()
                                        ->send();
                                    throw new Halt();
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('order_index')
            ->reorderable('order_index');
    }
}
