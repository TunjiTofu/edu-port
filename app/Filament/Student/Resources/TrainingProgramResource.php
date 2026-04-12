<?php

namespace App\Filament\Student\Resources;

use App\Enums\ProgramEnrollmentStatus;
use App\Filament\Student\Resources\TrainingProgramResource\Pages;
use App\Models\TrainingProgram;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TrainingProgramResource extends Resource
{
    protected static ?string $model           = TrainingProgram::class;
    protected static ?string $navigationIcon  = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'My Programs';
    protected static ?string $navigationGroup = 'Learning';
    protected static ?int    $navigationSort  = 3;

    public static function canViewAny(): bool  { return Auth::user()?->isStudent(); }
    public static function canCreate(): bool   { return false; }
    public static function canDelete($record): bool { return false; }
    public static function canEdit($record): bool   { return false; }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('enrollments', fn ($q) => $q->where('student_id', Auth::id()))
            ->withCount(['sections', 'enrollments'])
            ->with(['enrollments' => fn ($q) => $q->where('student_id', Auth::id())]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([

                        // Program image
                        Tables\Columns\ImageColumn::make('image')
                            ->label('')
                            ->disk('public')
                            ->circular()
                            ->size(64)
                            ->defaultImageUrl(asset('images/logo.png'))
                            ->grow(false)
                            ->extraImgAttributes(['class' => 'ring-2 ring-green-500/30 shadow-md']),


                        Tables\Columns\Layout\Stack::make([
                            Tables\Columns\TextColumn::make('name')
                                ->label('')
                                ->weight('bold')
                                ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                                ->searchable(),

                            Tables\Columns\TextColumn::make('description')
                                ->label('')->color('gray')->wrap()->limit(100),

                            // Stats badges
                            Tables\Columns\Layout\Grid::make(2)
                                ->schema([
                                    Tables\Columns\TextColumn::make('sections_count')
                                        ->badge()->color('info')
                                        ->formatStateUsing(fn ($state) => "📚 {$state} " . str('Section')->plural($state)),

                                    Tables\Columns\TextColumn::make('enrollments_count')
                                        ->badge()->color('success')
                                        ->formatStateUsing(fn ($state) => "👥 {$state} Enrolled"),
                                ]),

                            // Enrollment status + enrolled date
                            Tables\Columns\Layout\Grid::make(2)
                                ->schema([
                                    Tables\Columns\TextColumn::make('enrollments.enrolled_at')
                                        ->label('Enrolled')
                                        ->icon('heroicon-o-calendar')
                                        ->formatStateUsing(function ($state) {
                                            if (! $state) return 'Unknown';
                                            $carbon = $state instanceof \Carbon\Carbon ? $state : \Carbon\Carbon::parse($state);
                                            return 'Enrolled: ' . $carbon->format('M j, Y');
                                        }),

                                    Tables\Columns\TextColumn::make('enrollments.status')
                                        ->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            ProgramEnrollmentStatus::ACTIVE->value    => 'success',
                                            ProgramEnrollmentStatus::COMPLETED->value => 'info',
                                            ProgramEnrollmentStatus::PAUSED->value    => 'warning',
                                            default                                   => 'gray',
                                        })
                                        ->formatStateUsing(fn (string $state): string => match ($state) {
                                            ProgramEnrollmentStatus::ACTIVE->value    => '🟢 Active',
                                            ProgramEnrollmentStatus::COMPLETED->value => '✅ Completed',
                                            ProgramEnrollmentStatus::PAUSED->value    => '⏸ Paused',
                                            default                                   => ucfirst($state),
                                        }),
                                ]),

                            // Date range
                            Tables\Columns\Layout\Grid::make(2)
                                ->schema([
                                    Tables\Columns\TextColumn::make('start_date')
                                        ->icon('heroicon-o-play-circle')
                                        ->formatStateUsing(fn ($state) =>
                                            'Starts: ' . ($state instanceof \Carbon\Carbon
                                                ? $state->format('M j, Y')
                                                : \Carbon\Carbon::parse($state)->format('M j, Y'))
                                        ),

                                    Tables\Columns\TextColumn::make('end_date')
                                        ->icon('heroicon-o-stop-circle')
                                        ->formatStateUsing(fn ($state) =>
                                            'Ends: ' . ($state instanceof \Carbon\Carbon
                                                ? $state->format('M j, Y')
                                                : \Carbon\Carbon::parse($state)->format('M j, Y'))
                                        ),
                                ]),
                        ])->space(2)->grow(true),
                    ])->from('sm'),
                ])->space(3),
            ])
            ->contentGrid(['default' => 1, 'sm' => 1, 'md' => 2, 'xl' => 2])
            ->filters([
                Tables\Filters\SelectFilter::make('enrollment_status')
                    ->label('Status')
                    ->options([
                        ProgramEnrollmentStatus::ACTIVE->value    => 'Active',
                        ProgramEnrollmentStatus::COMPLETED->value => 'Completed',
                        ProgramEnrollmentStatus::PAUSED->value    => 'Paused',
                    ])
                    ->query(fn (Builder $query, array $data) =>
                    $data['value']
                        ? $query->whereHas('enrollments',
                        fn ($q) => $q->where('status', $data['value'])
                            ->where('student_id', Auth::id()))
                        : $query
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Open')
                    ->icon('heroicon-o-arrow-right')
                    ->color('primary')
                    ->button(),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No Programs Yet')
            ->emptyStateDescription("You're not enrolled in any training programs.")
            ->emptyStateIcon('heroicon-o-academic-cap')
            ->emptyStateActions([
                Tables\Actions\Action::make('browse')
                    ->label('Browse Available Programs')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->url(fn () => route('filament.student.resources.available-training-programs.index')),
            ])
            ->headerActions([
                Tables\Actions\Action::make('enroll_new')
                    ->label('Enroll in New Program')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->url(fn () => route('filament.student.resources.available-training-programs.index')),
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = TrainingProgram::whereHas('enrollments',
            fn ($q) => $q->where('student_id', Auth::id()))->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string { return 'info'; }
    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainingPrograms::route('/'),
            'view'  => Pages\ViewTrainingProgram::route('/{record}'),
        ];
    }
}
