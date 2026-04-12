<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Filament\Resources\TaskResource\RelationManagers;
use App\Models\Task;
use App\Models\TrainingProgram;
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

class TaskResource extends Resource
{
    protected static ?string $model          = Task::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Academic Management';
    protected static ?int    $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return Auth::user()?->isAdmin();
    }

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                FormSection::make('Task Information')
                    ->schema([
                        // FIX: was calling ->relationship() twice on the same field
                        Forms\Components\Select::make('section_id')
                            ->label('Section')
                            ->relationship(
                                name: 'section',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) =>
                                $query->where('is_active', true)->orderBy('order_index')
                            )
                            ->required()->searchable()->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $next = Task::where('section_id', $state)->max('order_index') + 1;
                                    $set('order_index', $next);
                                }
                            }),

                        Forms\Components\TextInput::make('title')
                            ->label('Task Title')
                            ->required()->maxLength(255),

                        Forms\Components\RichEditor::make('description')
                            ->required()->columnSpanFull()
                            ->toolbarButtons([
                                'bold', 'italic', 'underline', 'strike',
                                'bulletList', 'orderedList',
                                'h2', 'h3', 'paragraph',
                                'link', 'blockquote', 'codeBlock',
                            ]),
                    ])
                    ->columns(2),

                FormSection::make('Task Settings')
                    ->schema([
                        Forms\Components\TextInput::make('order_index')
                            ->label('Task Order')
                            ->numeric()->required()->minValue(1)
                            ->helperText('Order within the section.'),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Due Date')->required(),

                        Forms\Components\TextInput::make('max_score')
                            ->label('Maximum Score')
                            ->numeric()->minValue(0)->maxValue(10)
                            ->default(10)
                            ->helperText('Out of 10 maximum.'),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)->label('Active'),
                    ])
                    ->columns(4),

                FormSection::make('Grading Instructions')
                    ->schema([
                        Forms\Components\RichEditor::make('instructions')
                            ->label('Instructions for Reviewers')
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold', 'italic', 'underline',
                                'bulletList', 'orderedList',
                                'h3', 'paragraph', 'blockquote',
                            ]),
                    ]),
            ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('section.trainingProgram.name')
                    ->label('Program')
                    ->searchable()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('section.name')
                    ->label('Section')
                    ->searchable()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()->sortable()->weight('bold'),

                Tables\Columns\TextColumn::make('order_index')
                    ->label('Order')->sortable()
                    ->alignCenter()->badge()->color('primary'),

                Tables\Columns\TextColumn::make('max_score')
                    ->label('Max Score')->alignCenter(),

                Tables\Columns\TextColumn::make('rubrics_count')
                    ->label('Rubrics')->counts('rubrics')
                    ->alignCenter()->badge()->color('gray'),

                Tables\Columns\TextColumn::make('due_date')
                    ->date()->sortable()
                    ->color(fn ($record) => $record->due_date?->isPast() ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('submissions_count')
                    ->label('Submissions')->counts('submissions')
                    ->alignCenter()->badge()->color('info'),

                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),

                SelectFilter::make('training_program')
                    ->label('Program')
                    ->options(fn () => TrainingProgram::pluck('name', 'id'))
                    ->query(fn (Builder $query, $data) =>
                    $data['value']
                        ? $query->whereHas('section.trainingProgram',
                        fn ($q) => $q->where('id', $data['value']))
                        : $query
                    ),

                SelectFilter::make('section')
                    ->relationship('section', 'name')
                    ->searchable()->preload(),

                Tables\Filters\TernaryFilter::make('is_active')->label('Status'),

                Tables\Filters\Filter::make('due_date')
                    ->form([
                        Forms\Components\DatePicker::make('due_from')->label('Due From'),
                        Forms\Components\DatePicker::make('due_until')->label('Due Until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder =>
                    $query
                        ->when($data['due_from'],
                            fn ($q, $date) => $q->whereDate('due_date', '>=', $date))
                        ->when($data['due_until'],
                            fn ($q, $date) => $q->whereDate('due_date', '<=', $date))
                    ),

                Tables\Filters\Filter::make('has_rubrics')
                    ->label('Has Rubrics')
                    ->query(fn (Builder $q) => $q->has('rubrics'))
                    ->toggle(),

                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue Only')
                    ->query(fn (Builder $q) =>
                    $q->where('is_active', true)->where('due_date', '<', now())
                    )
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('manage_rubrics')
                    ->label('Rubrics')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('info')
                    ->url(fn (Task $record): string =>
                    route('filament.admin.resources.rubrics.index',
                        ['tableFilters[task][value]' => $record->id])
                    )
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Task $record) {
                        if ($record->submissions()->exists()) {
                            Notification::make()->title('Denied')
                                ->body('Remove all submissions for this task before deleting.')
                                ->danger()->persistent()->send();
                            throw new Halt();
                        }
                    }),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
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
            RelationManagers\RubricsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'view'   => Pages\ViewTask::route('/{record}'),
            'edit'   => Pages\EditTask::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['section.trainingProgram'])
            ->withCount(['submissions', 'rubrics'])
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
