<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Filament\Resources\TaskResource\RelationManagers;
use App\Models\Task;
use App\Models\Section; // Add this import for your Section model
use Filament\Forms;
use Filament\Forms\Components\Section as FormSection; // Alias the form component
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

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Academic Management';
    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return Auth::user()?->isAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                FormSection::make('Task Information')
                    ->schema([
                        Forms\Components\Select::make('section_id')
                            ->label('Section')
                            ->relationship('section', 'name')
                            ->relationship(
                                name: 'section',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn(Builder $query) => $query->where('is_active', true)->orderBy('order_index')
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $nextOrder = Task::where('section_id', $state)->max('order_index') + 1;
                                    $set('order_index', $nextOrder);
                                }
                            }),

                        Forms\Components\TextInput::make('title')
                            ->label('Task Title')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\RichEditor::make('description')
                            ->required()
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'bulletList',
                                'orderedList',
                                'h2',
                                'h3',
                                'paragraph',
                                'link',
                                'blockquote',
                                'codeBlock'
                            ]),


                    ])->columns(1),

                FormSection::make('Task Settings')
                    ->schema([

                        Forms\Components\TextInput::make('order_index')
                            ->label('Task Order')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->helperText('Order in which this task appears in the section'),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Due Date & Time')
                            ->required(),

                        Forms\Components\TextInput::make('max_score')
                            ->label('Maximum Score for Task')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10) // Enforce maximum value of 10
                            ->default(function (?Task $record) {
                                return $record?->max_score ?? 10;
                            })
                            ->helperText('Total maximum score for tasks (max 10)')
                            ->rules([
                                'numeric',
                                'min:0',
                                'max:10',
                            ]),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active Status'),
                    ])->columns(3),

                FormSection::make('Grading Instructions')
                    ->schema([
                        Forms\Components\RichEditor::make('instructions')
                            ->label('Grading Rubric')
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                                'h3',
                                'paragraph',
                                'blockquote'
                            ])
                            ->helperText('Detailed instructions for reviewers on how to grade this task'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('section.name')
                    ->label('Section')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('section.trainingProgram.name')
                    ->label('Program')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('order_index')
                    ->label('Order')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('max_score')
                    ->label('Max Score')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->color(fn($record) => $record->due_date < now() ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('submissions_count')
                    ->label('Submissions')
                    ->counts('submissions')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

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
                    ->label('Training Program')
                    ->options(function () {
                        return \App\Models\TrainingProgram::pluck('name', 'id')->toArray();
                    })
                    ->query(function (Builder $query, $data) {
                        if ($data['value']) {
                            $query->whereHas('section.trainingProgram', function ($q) use ($data) {
                                $q->where('id', $data['value']);
                            });
                        }
                    }),

                SelectFilter::make('section')
                    ->relationship('section', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),

                Tables\Filters\Filter::make('due_date')
                    ->form([
                        Forms\Components\DatePicker::make('due_from')
                            ->label('Due Date From'),
                        Forms\Components\DatePicker::make('due_until')
                            ->label('Due Date Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['due_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('due_date', '>=', $date),
                            )
                            ->when(
                                $data['due_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('due_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Task $record) {
                        // Prevent deletion if there are existing submissions
                        if ($record->submissions()->exists()) {
                            Notification::make()
                                ->title('Request Denied')
                                ->body('Cannot delete task with existing submissions. Please remove all submissions before deleting.')
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
                    // Tables\Actions\ForceDeleteBulkAction::make(),
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
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'view' => Pages\ViewTask::route('/{record}'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['section.trainingProgram'])
            ->withCount('submissions')
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
