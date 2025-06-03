<?php

namespace App\Filament\Student\Resources;

use App\Enums\ProgramEnrollmentStatus;
use App\Filament\Student\Resources\TrainingProgramResource\Pages;
use App\Filament\Student\Resources\TrainingProgramResource\RelationManagers;
use App\Models\TrainingProgram;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class TrainingProgramResource extends Resource
{
    protected static ?string $model = TrainingProgram::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'My Programs';
    protected static ?string $navigationGroup = 'Learning';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return Auth::user()?->isStudent();
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
                Tables\Columns\ImageColumn::make('thumbnail')
                    ->label('Thumbnail')
                    ->circular()
                    ->size(60)
                    ->defaultImageUrl(asset('images/default-program.png')),

                Tables\Columns\TextColumn::make('name')
                    ->label('Program Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn($record) => $record->description),

                Tables\Columns\TextColumn::make('sections_count')
                    ->label('Sections')
                    ->counts('sections')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('tasks_count')
                    ->label('Total Tasks')
                    ->getStateUsing(fn($record) => $record->sections->sum(fn($section) => $section->tasks->count()))
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('enrollments.enrolled_at')
                    ->label('Enrolled Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('enrollments.status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        ProgramEnrollmentStatus::ACTIVE->value => 'success',
                        ProgramEnrollmentStatus::COMPLETED->value => 'warning',
                        ProgramEnrollmentStatus::PAUSED->value => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => str($state)->title())
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
            ->emptyStateIcon('heroicon-o-academic-cap');
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
