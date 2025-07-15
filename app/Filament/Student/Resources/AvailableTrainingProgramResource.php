<?php

namespace App\Filament\Student\Resources;

use App\Enums\ProgramEnrollmentStatus;
use App\Filament\Student\Resources\AvailableTrainingProgramResource\Pages;
use App\Models\TrainingProgram;
use App\Models\ProgramEnrollment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class AvailableTrainingProgramResource extends Resource
{
    protected static ?string $model = TrainingProgram::class;
    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';
    protected static ?string $navigationLabel = 'Available Training Programs';
    protected static ?string $navigationGroup = 'Learning';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return Auth::user()?->isStudent();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('is_active', true)
            ->whereDoesntHave('enrollments', function ($query) {
                $query->where('student_id', Auth::user()->id);
            })
            ->with(['sections', 'sections.tasks', 'enrollments'])
            ->withCount(['sections', 'enrollments']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Image')
                    ->circular()
                    ->size(60)
                    ->disk('public')
                    ->defaultImageUrl(asset('images/logo.png')),

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

                Tables\Columns\TextColumn::make('registration_deadline')
                    ->label('Registration Deadline')
                    ->date()
                    ->badge()
                    ->color(fn($state) => $state && now()->diffInDays($state) <= 7 ? 'danger' : 'info')
                    ->formatStateUsing(fn($state) => $state ? $state->format('M j, Y') : 'No deadline'),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('End Date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('registration_deadline')
                    ->form([
                        Forms\Components\DatePicker::make('deadline_from')
                            ->label('Registration deadline from'),
                        Forms\Components\DatePicker::make('deadline_to')
                            ->label('Registration deadline to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['deadline_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('registration_deadline', '>=', $date),
                            )
                            ->when(
                                $data['deadline_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('registration_deadline', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('start_date')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Starting from'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('enroll')
                    ->label('Enroll')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Enroll in Training Program')
                    ->modalDescription(fn($record) => "Are you sure you want to enroll in '{$record->name}'? This will add the program to your learning dashboard.")
                    ->modalSubmitActionLabel('Enroll Now')
                    ->action(function (TrainingProgram $record) {
                        try {
                            // Check if registration is still open
                            if (!$record->isRegistrationOpen()) {
                                Notification::make()
                                    ->title('Registration Closed')
                                    ->body('Registration for this program has closed.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // Check if program is fully enrolled
                            if ($record->isFullyEnrolled()) {
                                Notification::make()
                                    ->title('Program Full')
                                    ->body('This program has reached its maximum enrollment capacity.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // Check if already enrolled (safety check)
                            $existingEnrollment = ProgramEnrollment::where('student_id', Auth::user()->id)
                                ->where('training_program_id', $record->id)
                                ->first();

                            if ($existingEnrollment) {
                                Notification::make()
                                    ->title('Already Enrolled')
                                    ->body('You are already enrolled in this program.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // Create new enrollment
                            ProgramEnrollment::create([
                                'student_id' => Auth::user()->id,
                                'training_program_id' => $record->id,
                                'enrolled_at' => now(),
                                'status' => ProgramEnrollmentStatus::ACTIVE->value,
                            ]);

                            Notification::make()
                                ->title('Enrollment Successful!')
                                ->body("You have successfully enrolled in '{$record->name}'. You can now access it from your programs.")
                                ->success()
                                ->send();

                            // Redirect to My Programs
                            return redirect()->to('/student/training-programs');

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Enrollment Failed')
                                ->body('There was an error enrolling in the program. Please try again.')
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\ViewAction::make()
                    ->label('View Details')
                    ->color('primary')
                    ->icon('heroicon-o-eye'),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No Available Programs')
            ->emptyStateDescription('There are no training programs available for enrollment at this time.')
            ->emptyStateIcon('heroicon-o-plus-circle');
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
            'index' => Pages\ListAvailableTrainingPrograms::route('/'),
            'view' => Pages\ViewAvailableTrainingProgram::route('/{record}'),
        ];
    }
}
