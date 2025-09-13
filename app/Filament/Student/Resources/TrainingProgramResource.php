<?php

namespace App\Filament\Student\Resources;

use App\Enums\ProgramEnrollmentStatus;
use App\Filament\Student\Resources\TrainingProgramResource\Pages;
use App\Filament\Student\Resources\TrainingProgramResource\RelationManagers;
use App\Models\TrainingProgram;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TrainingProgramResource extends Resource
{
    protected static ?string $model = TrainingProgram::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'My Training Programs';
    protected static ?string $navigationGroup = 'Learning';
    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return Auth::user()?->isStudent();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('enrollments', function ($query) {
                $query->where('student_id', Auth::user()->id);
            })
            ->with(['sections.tasks', 'sections.tasks.submissions' ,'enrollments' => function ($query) {
                $query->where('student_id', Auth::user()->id);
            }]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\ImageColumn::make('image')
                            ->label('')
                            ->disk(config('filesystems.default'))
                            ->visibility('private')
                            ->circular()
                            ->size(80)
                            ->defaultImageUrl('/images/default-program.png')
                            ->grow(false),

                        Tables\Columns\Layout\Stack::make([
                            Tables\Columns\TextColumn::make('name')
                                ->label('')
                                ->searchable()
                                ->sortable()
                                ->weight('bold')
                                ->size(Tables\Columns\TextColumn\TextColumnSize::Large),

                            Tables\Columns\TextColumn::make('description')
                                ->label('')
                                ->color('gray')
                                ->wrap(),

                            Tables\Columns\Layout\Grid::make(2)
                                ->schema([
                                    Tables\Columns\TextColumn::make('sections_count')
                                        ->label('Sections')
                                        ->counts('sections')
                                        ->badge()
                                        ->color('info')
                                        ->formatStateUsing(fn($state) => 'Sections: ' . $state),

                                    Tables\Columns\TextColumn::make('tasks_count')
                                        ->label('Total Tasks')
                                        ->getStateUsing(fn($record) => $record->sections->sum(fn($section) => $section->tasks->count()))
                                        ->badge()
                                        ->color('warning')
                                        ->formatStateUsing(fn($state) => 'Tasks: ' . $state),
                                ]),

                            Tables\Columns\TextColumn::make('')
                                ->label('')
                                ->formatStateUsing(fn() => '')
                                ->extraAttributes(['style' => 'height: 8px;']),

                            Tables\Columns\Layout\Grid::make(2)
                                ->schema([
                                    Tables\Columns\TextColumn::make('enrollments.enrolled_at')
                                        ->label('Enrolled Date')
                                        ->date()
                                        ->sortable()
                                        ->icon('heroicon-o-calendar')
                                        ->formatStateUsing(fn($state) => 'Enrolled: ' . $state),

                                    Tables\Columns\TextColumn::make('enrollments.status')
                                        ->label('Status')
                                        ->badge()
                                        ->color(fn(string $state): string => match ($state) {
                                            ProgramEnrollmentStatus::ACTIVE->value => 'success',
                                            ProgramEnrollmentStatus::COMPLETED->value => 'warning',
                                            ProgramEnrollmentStatus::PAUSED->value => 'danger',
                                            default => 'gray',
                                        })
                                        ->formatStateUsing(fn(string $state): string => 'Status: ' . str($state)->title())
                                ]),
                        ])->grow(true),
                    ])->from('md'),
                ])->space(3),
            ])
            ->contentGrid([
                'md' => 1,
                'xl' => 1,
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('enrollment_status')
                    ->label('Enrollment Status')
                    ->options([
                        ProgramEnrollmentStatus::ACTIVE->value => 'Active',
                        ProgramEnrollmentStatus::COMPLETED->value => 'Completed',
                        ProgramEnrollmentStatus::PAUSED->value => 'Paused',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value']) {
                            $query->whereHas('enrollments', function ($q) use ($data) {
                                $q->where('status', $data['value']);
                            });
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('View Details')
                    ->color('primary')
                    ->icon('heroicon-o-eye'),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No Programs Enrolled')
            ->emptyStateDescription('You are not currently enrolled in any training programs.')
            ->emptyStateIcon('heroicon-o-academic-cap')
            ->emptyStateActions([
                Tables\Actions\Action::make('browse_programs')
                    ->label('Browse Available Programs')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->url(fn() => route('filament.student.resources.available-training-programs.index'))
            ])
            ->headerActions([
                Tables\Actions\Action::make('enroll_new')
                    ->label('Enroll in New Program')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->url(fn() => route('filament.student.resources.available-training-programs.index'))
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
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
            'index' => Pages\ListTrainingPrograms::route('/'),
            'create' => Pages\CreateTrainingProgram::route('/create'),
            'view' => Pages\ViewTrainingProgram::route('/{record}'),
            'edit' => Pages\EditTrainingProgram::route('/{record}/edit'),
        ];
    }
}
