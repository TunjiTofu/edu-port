<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SectionResource\Pages;
use App\Models\Section;
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

class SectionResource extends Resource
{
    protected static ?string $model          = Section::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationGroup = 'Academic Management';
    protected static ?int    $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return Auth::user()?->isAdmin();
    }

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                FormSection::make('Section Information')
                    ->schema([
                        Forms\Components\Select::make('training_program_id')
                            ->label('Training Program')
                            ->relationship(
                                name: 'trainingProgram',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => $query->where('is_active', true)
                            )
                            ->required()->searchable()->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $next = Section::where('training_program_id', $state)->max('order_index') + 1;
                                    $set('order_index', $next);
                                }
                            }),

                        Forms\Components\TextInput::make('name')
                            ->required()->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)->columnSpanFull(),
                    ])
                    ->columns(2),

                FormSection::make('Section Settings')
                    ->schema([
                        Forms\Components\TextInput::make('order_index')
                            ->label('Section Order')
                            ->required()->minValue(1)
                            ->readOnly()
                            ->helperText('Auto-set based on existing sections in the program.'),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)->label('Active'),
                    ])
                    ->columns(2),
            ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('trainingProgram.name')
                    ->label('Program')
                    ->searchable()->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()->sortable()->weight('bold'),

                Tables\Columns\TextColumn::make('order_index')
                    ->label('Order')->sortable()
                    ->alignCenter()->badge()->color('primary'),

                Tables\Columns\TextColumn::make('tasks_count')
                    ->label('Tasks')->counts('tasks')
                    ->alignCenter()->badge()->color('success'),

                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order_index')
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                SelectFilter::make('training_program')
                    ->relationship('trainingProgram', 'name')
                    ->searchable()->preload(),
                Tables\Filters\TernaryFilter::make('is_active')->label('Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Section $record) {
                        if ($record->tasks()->exists()) {
                            Notification::make()->title('Denied')
                                ->body('Remove all tasks from this section before deleting.')
                                ->danger()->persistent()->send();
                            throw new Halt();
                        }
                    }),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                if ($record->tasks()->exists()) {
                                    Notification::make()->title('Denied')
                                        ->body('One or more sections have tasks. Remove them first.')
                                        ->danger()->persistent()->send();
                                    throw new Halt();
                                }
                            }
                        }),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSections::route('/'),
            'create' => Pages\CreateSection::route('/create'),
            'view'   => Pages\ViewSection::route('/{record}'),
            'edit'   => Pages\EditSection::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->withCount('tasks')
            ->with('trainingProgram')
            ->join('training_programs', 'training_programs.id', '=', 'sections.training_program_id')
            ->orderBy('training_programs.name')
            ->orderBy('sections.order_index')
            ->select('sections.*');
    }
}
