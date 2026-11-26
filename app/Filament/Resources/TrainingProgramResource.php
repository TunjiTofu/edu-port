<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrainingProgramResource\Pages;
use App\Filament\Resources\TrainingProgramResource\RelationManagers;
use App\Models\TrainingProgram;
use Auth;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\FileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class TrainingProgramResource extends Resource
{
    protected static ?string $model = TrainingProgram::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Academic Management';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return Auth::user()?->isAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Program Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('code')
                            ->label('Program Code')
                            ->required()
                            ->unique(TrainingProgram::class, 'code', ignoreRecord: true)
                            ->maxLength(20)
                            ->alphaNum(),

                        Forms\Components\FileUpload::make('image')
                            ->label('Program Image')
                            ->image()
                            ->disk(config('filesystems.default'))
                            ->directory('training-programs')
                            ->visibility('private')
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file): string =>
                                    now()->format('Y-m-d_H-i-s') . '_' .
                                    str_replace(' ', '_', strtolower($file->getClientOriginalName()))
                            )
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),

                    ])->columns(2),

                Section::make('Program Status & Dates')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active Status'),

                        Forms\Components\DatePicker::make('registration_deadline')
                            ->label('Registration Deadline')
                            ->before('start_date'),
                    ])->columns(2),

                Section::make('Schedule')
                    ->schema(function () {
                        $calculateDuration = function ($get, $set) {
                            $start = $get('start_date');
                            $end = $get('end_date');
                            if ($start && $end) {
                                $days = \Carbon\Carbon::parse($start)->diffInDays(\Carbon\Carbon::parse($end));
                                $weeks = floor($days / 7);
                                $remainingDays = $days % 7;

                                $display = $weeks . ' week' . ($weeks != 1 ? 's' : '');
                                if ($remainingDays > 0) {
                                    $display .= ' ' . $remainingDays . ' day' . ($remainingDays != 1 ? 's' : '');
                                }

                                $set('duration_display', $display);
                            } else {
                                $set('duration_display', 'N/A');
                            }
                        };

                        return [
                            Forms\Components\DatePicker::make('start_date')
                                ->required()
                                ->live()
                                ->afterStateUpdated($calculateDuration),

                            Forms\Components\DatePicker::make('end_date')
                                ->required()
                                ->live()
                                ->afterStateUpdated($calculateDuration),

                            Forms\Components\TextInput::make('duration_display')
                                ->label('Duration')
                                ->readOnly()
                                ->default('N/A'),
                        ];
                    })
                    ->columns(3),

                Section::make('Program Settings')
                    ->schema([
                        Forms\Components\TextInput::make('max_students')
                            ->label('Maximum Students')
                            ->numeric()
                            ->minValue(1)
                            ->default(100),

                        Forms\Components\TextInput::make('passing_score')
                            ->label('Passing Score (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(70)
                            ->suffix('%'),

                        Forms\Components\Select::make('submission_format')
                            ->label('Allowed Submission Formats')
                            ->options([
                                'pdf' => 'PDF Only',
                                'doc' => 'Word Documents Only',
                            ])
                            ->default('both')
                            ->required(),
                    ])->columns(3),

                // ── Scoring Configuration ─────────────────────────────────────────
                Section::make('Scoring Configuration')
                    ->description('Configure how portfolio and interview scores are weighted and what cutoffs determine candidate progression.')
                    ->icon('heroicon-o-scale')
                    ->schema([
                        // ── Weights ──────────────────────────────────────────────
                        Forms\Components\TextInput::make('portfolio_weight')
                            ->label('Portfolio Weight (%)')
                            ->helperText('Percentage of the total score allocated to the portfolio submission.')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(99)
                            ->default(60)
                            ->suffix('%')
                            ->live()
                            ->required()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                // Keep interview_weight in sync so they always sum to 100
                                $portfolio = (float) $state;
                                if ($portfolio >= 1 && $portfolio <= 99) {
                                    $set('interview_weight', round(100 - $portfolio, 2));
                                }
                            }),

                        Forms\Components\TextInput::make('interview_weight')
                            ->label('Interview Weight (%)')
                            ->helperText('Percentage of the total score allocated to the interview. Auto-calculated from Portfolio Weight.')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(99)
                            ->default(40)
                            ->suffix('%')
                            ->live()
                            ->required()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                // Reverse sync — updating interview adjusts portfolio
                                $interview = (float) $state;
                                if ($interview >= 1 && $interview <= 99) {
                                    $set('portfolio_weight', round(100 - $interview, 2));
                                }
                            }),

                        // ── Cutoff marks ─────────────────────────────────────────
                        Forms\Components\TextInput::make('portfolio_cutoff')
                            ->label('Portfolio Cutoff (%)')
                            ->helperText('Minimum portfolio percentage a candidate must achieve to qualify for interview. Leave blank to disable.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->nullable()
                            ->suffix('%')
                            ->placeholder('e.g. 60'),

                        Forms\Components\TextInput::make('total_cutoff')
                            ->label('Total Score Cutoff (%)')
                            ->helperText('Minimum combined score (out of 100) a candidate must achieve to qualify for investiture. Leave blank to disable.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->nullable()
                            ->suffix('%')
                            ->placeholder('e.g. 50'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Image')
                    ->disk(config('filesystems.default'))
                    ->visibility('private')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl('/images/default-program.png'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('duration_weeks')
                    ->label('Duration')
                    ->suffix(' weeks')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('students_count')
                    ->label('Students')
                    ->counts('students')
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('sections_count')
                    ->label('Sections')
                    ->counts('sections')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\TextColumn::make('portfolio_weight')
                    ->label('Weights')
                    ->formatStateUsing(fn ($record) => "{$record->portfolio_weight}% / {$record->interview_weight}%")
                    ->description('Portfolio / Interview')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('portfolio_cutoff')
                    ->label('Cutoffs')
                    ->formatStateUsing(fn ($record) =>
                        ($record->portfolio_cutoff ? $record->portfolio_cutoff . '%' : '—') .
                        ' / ' .
                        ($record->total_cutoff ? $record->total_cutoff . '%' : '—')
                    )
                    ->description('Portfolio / Total')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),

                Tables\Filters\Filter::make('start_date')
                    ->form([
                        Forms\Components\DatePicker::make('start_from')
                            ->label('Start Date From'),
                        Forms\Components\DatePicker::make('start_until')
                            ->label('Start Date Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['start_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (TrainingProgram $record) {
                        if ($record->students()->exists()) {
                            Notification::make()
                                ->title('Request Denied')
                                ->body('Cannot delete training program with enrolled students.')
                                ->danger()
                                ->persistent()
                                ->send();
                            throw new Halt();
                        }

                        if (TrainingProgram::count() <= 1) {
                            Notification::make()
                                ->title('Request Denied')
                                ->body('You cannot delete the last training program.')
                                ->danger()
                                ->persistent()
                                ->send();
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
                            $records->each(function (TrainingProgram $record) {
                                if ($record->students()->exists()) {
                                    Notification::make()
                                        ->title('Request Denied')
                                        ->body('Cannot delete training program with enrolled students.')
                                        ->danger()
                                        ->persistent()
                                        ->send();
                                    throw new Halt();
                                }
                            });

                            $TrainingCount = TrainingProgram::count();
                            $selectedRecords = $records->count();
                            if ($TrainingCount - $selectedRecords < 1) {
                                Notification::make()
                                    ->title('Request Denied')
                                    ->body('You cannot delete the last training program')
                                    ->danger()
                                    ->persistent()
                                    ->send();
                                throw new Halt();
                            }
                        }),
                ]),
                Tables\Actions\RestoreBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTrainingPrograms::route('/'),
            'create' => Pages\CreateTrainingProgram::route('/create'),
            'view'   => Pages\ViewTrainingProgram::route('/{record}'),
            'edit'   => Pages\EditTrainingProgram::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['students', 'sections'])
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    protected function calculateDuration(Forms\Get $get, Forms\Set $set): void
    {
        $start = $get('start_date');
        $end   = $get('end_date');

        if ($start && $end) {
            $weeks = \Carbon\Carbon::parse($start)->diffInWeeks(\Carbon\Carbon::parse($end));
            $set('duration_weeks', $weeks);
        } else {
            $set('duration_weeks', 0);
        }
    }
}
