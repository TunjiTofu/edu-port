<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SectionResource\Pages;
use App\Filament\Resources\SectionResource\RelationManagers;
use App\Models\Section;
use App\Models\TrainingProgram;
use Filament\Forms;
use Filament\Forms\Components\Section as ComponentsSection;
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

class SectionResource extends Resource
{
    protected static ?string $model = Section::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationGroup = 'Academic Management';
    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return Auth::user()?->isAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                ComponentsSection::make('Section Information')
                    ->schema([
                        Forms\Components\Select::make('training_program_id')
                            ->label('Training Program')
                            // ->relationship('trainingProgram', 'name')
                            ->relationship(
                                name: 'trainingProgram',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn(Builder $query) => $query->where('is_active', true)
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $program = TrainingProgram::find($state);
                                    $nextOrder = Section::where('training_program_id', $state)->max('order_index') + 1;
                                    $set('order_index', $nextOrder);
                                }
                            }),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),

                    ])->columns(2),

                ComponentsSection::make('Section Settings')
                    ->schema([
                        Forms\Components\TextInput::make('order_index')
                            ->label('Section Order')
                            // ->numeric()
                            ->required()
                            ->minValue(1)
                            ->readonly()
                            ->helperText('Order in which this section appears in the program'),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active Status'),

                    ])->columns(2),

                // ComponentsSection::make('Requirements')
                //     ->schema([
                //         Forms\Components\TextInput::make('prerequisite_sections')
                //             ->label('Prerequisite Section IDs')
                //             ->helperText('Comma-separated section IDs that must be completed first')
                //             ->placeholder('1,2,3'),

                //         Forms\Components\Toggle::make('requires_all_tasks_complete')
                //             ->label('Require All Tasks Complete')
                //             ->default(true)
                //             ->helperText('Students must complete all tasks in this section'),

                //         Forms\Components\TextInput::make('minimum_score_to_pass')
                //             ->label('Minimum Score to Pass Section (%)')
                //             ->numeric()
                //             ->minValue(0)
                //             ->maxValue(100)
                //             ->default(70)
                //             ->suffix('%'),
                //     ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('trainingProgram.name')
                    ->label('Training Program')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('order_index')
                    ->label('Order')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('tasks_count')
                    ->label('Tasks')
                    ->counts('tasks')
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

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
                SelectFilter::make('training_program')
                    ->relationship('trainingProgram', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),

            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Section $record) {
                        // Prevent deletion if there are existing tasks
                        if ($record->tasks()->exists()) {
                            Notification::make()
                                ->title('Request Denied')
                                ->body('Cannot delete section with existing tasks. Please remove all tasks before deleting.')
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
                            foreach ($records as $record) {
                                if ($record->tasks()->exists()) {
                                    Notification::make()
                                        ->title('Request Denied')
                                        ->body('Cannot delete section with existing tasks. Please remove all tasks in selected sections before deleting.')
                                        ->danger()
                                        ->persistent()
                                        ->send();
                                    throw new Halt();
                                }
                            }
                        }),
                    // Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListSections::route('/'),
            'create' => Pages\CreateSection::route('/create'),
            'view' => Pages\ViewSection::route('/{record}'),
            'edit' => Pages\EditSection::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        // Only apply the join and ordering for index queries
        if (request()->routeIs('filament.admin.resources.sections.index')) {
            $query->join('training_programs', 'training_programs.id', '=', 'sections.training_program_id')
                ->orderBy('training_programs.name')
                ->orderBy('order_index')
                ->select('sections.*');
        }

        return $query;
    }
}
