<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResultPublicationResource\Pages;
use App\Models\ResultPublication;
use App\Models\Task;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ResultPublicationResource extends Resource
{
    protected static ?string $model           = ResultPublication::class;
    protected static ?string $navigationIcon  = 'heroicon-o-document-check';
    protected static ?string $navigationLabel = 'Result Publications';
    protected static ?string $modelLabel      = 'Result Publication';
    protected static ?string $pluralModelLabel = 'Result Publications';
    protected static ?string $navigationGroup = 'Academic Management';
    protected static ?int    $navigationSort  = 5;

    public static function canViewAny(): bool
    {
        return Auth::user()?->isAdmin();
    }

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Result Publication')
                    ->schema([
                        Forms\Components\Select::make('task_id')
                            ->label('Task')
                            ->relationship('task', 'title')
                            ->searchable()->preload()->required()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                if ($state) {
                                    $exists = ResultPublication::where('task_id', $state)->exists();
                                    if ($exists) {
                                        Notification::make()
                                            ->title('Already Published')
                                            ->body('A result publication already exists for this task.')
                                            ->warning()->send();
                                    }
                                }
                            })
                            ->helperText('Each task can only have one result publication.'),

                        Forms\Components\Toggle::make('is_published')
                            ->label('Published')
                            ->default(false)
                            ->helperText('When on, candidates can see their results for this task.'),
                    ])
                    ->columns(2),
            ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('task.section.trainingProgram.name')
                    ->label('Program')
                    ->searchable()->sortable()->badge()->color('info'),

                Tables\Columns\TextColumn::make('task.section.name')
                    ->label('Section')
                    ->searchable()->sortable()->toggleable(),

                Tables\Columns\TextColumn::make('task.title')
                    ->label('Task')
                    ->searchable()->sortable()->weight('bold'),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')->falseColor('gray'),

                Tables\Columns\TextColumn::make('publisher.name')
                    ->label('Published By')
                    ->placeholder('—')->toggleable(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Published At')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_published')->label('Published Status'),
                SelectFilter::make('training_program')
                    ->label('Program')
                    ->options(fn () => \App\Models\TrainingProgram::pluck('name', 'id'))
                    ->query(fn (Builder $query, $data) =>
                    $data['value']
                        ? $query->whereHas('task.section.trainingProgram',
                        fn ($q) => $q->where('id', $data['value']))
                        : $query
                    ),
            ])
            ->actions([
                // Quick publish/unpublish toggle directly from the table
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
                    ->action(function (ResultPublication $record) {
                        if ($record->is_published) {
                            $record->unpublish();
                            Notification::make()->title('Results unpublished.')->warning()->send();
                        } else {
                            $record->publish(Auth::user());
                            Notification::make()->title('Results published — candidates can now see their scores.')->success()->send();
                        }
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('publish_all')
                        ->label('Publish Selected')
                        ->icon('heroicon-o-eye')->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->publish(Auth::user());
                            Notification::make()->title('Selected results published.')->success()->send();
                        }),
                    Tables\Actions\BulkAction::make('unpublish_all')
                        ->label('Unpublish Selected')
                        ->icon('heroicon-o-eye-slash')->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->unpublish();
                            Notification::make()->title('Selected results unpublished.')->warning()->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListResultPublications::route('/'),
            'create' => Pages\CreateResultPublication::route('/create'),
//            'view'   => Pages\ViewResultPublication::route('/{record}'),
            'edit'   => Pages\EditResultPublication::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['task.section.trainingProgram', 'publisher'])
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
