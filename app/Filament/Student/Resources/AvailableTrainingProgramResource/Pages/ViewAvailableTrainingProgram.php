<?php

namespace App\Filament\Student\Resources\AvailableTrainingProgramResource\Pages;

use App\Filament\Student\Resources\AvailableTrainingProgramResource;
use App\Models\ProgramEnrollment;
use App\Enums\ProgramEnrollmentStatus;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Auth;

class ViewAvailableTrainingProgram extends ViewRecord
{
    protected static string $resource = AvailableTrainingProgramResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Program Overview')
                    ->schema([
                        Infolists\Components\Split::make([
                            Infolists\Components\Grid::make(2)
                                ->schema([
                                    Infolists\Components\ImageEntry::make('image')
                                        ->label('Program Image')
                                        ->circular()
                                        ->size(120)
                                        ->defaultImageUrl(asset('images/logo.png')),


                                    Infolists\Components\Group::make([
                                        Infolists\Components\TextEntry::make('name')
                                            ->label('Program Name')
                                            ->weight('bold')
                                            ->size('lg'),

                                        Infolists\Components\TextEntry::make('description')
                                            ->label('Description')
                                            ->prose()
                                            ->markdown()
                                            ->columnSpanFull(),
                                    ]),
                                ])
                        ])
                    ])
                    ->collapsible(false),

                Infolists\Components\Section::make('Program Details')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('duration_weeks')
                                    ->label('Duration')
                                    ->badge()
                                    ->color('primary'),

                                Infolists\Components\TextEntry::make('start_date')
                                    ->label('Start Date')
                                    ->date()
                                    ->badge()
                                    ->color('success'),

                                Infolists\Components\TextEntry::make('end_date')
                                    ->label('End Date')
                                    ->date()
                                    ->badge()
                                    ->color('warning'),
                            ]),
                    ])
                    ->collapsible(false),

                Infolists\Components\Section::make('Program Statistics')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('sections_count')
                                    ->label('Total Sections')
                                    ->numeric()
                                    ->badge()
                                    ->color('info'),

                                Infolists\Components\TextEntry::make('tasks_count')
                                    ->label('Total Tasks')
                                    ->getStateUsing(fn($record) => $record->sections->sum(fn($section) => $section->tasks->count()))
                                    ->numeric()
                                    ->badge()
                                    ->color('warning'),

                                Infolists\Components\TextEntry::make('students_count')
                                    ->label('Enrolled Students')
                                    ->getStateUsing(fn($record) => $record->enrollments->count())
                                    ->numeric()
                                    ->badge()
                                    ->color('success'),

                                Infolists\Components\TextEntry::make('is_active')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn($state) => $state ? 'success' : 'danger')
                                    ->formatStateUsing(fn($state) => $state ? 'Active' : 'Inactive'),
                            ]),
                    ])
                    ->collapsible(false),

                Infolists\Components\Section::make('Program Sections')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('sections')
                            ->schema([
                                Infolists\Components\Grid::make(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('title')
                                            ->label('Section Title')
                                            ->weight('bold'),

                                        Infolists\Components\TextEntry::make('order_index')
                                            ->label('Order')
                                            ->badge()
                                            ->color('gray'),

                                        Infolists\Components\TextEntry::make('tasks_count')
                                            ->label('Tasks')
                                            ->getStateUsing(fn($record) => $record->tasks->count())
                                            ->badge()
                                            ->color('info'),
                                    ]),

                                Infolists\Components\TextEntry::make('description')
                                    ->label('Description')
                                    ->prose()
                                    ->markdown()
                                    ->columnSpanFull()
                                    ->visible(fn($record) => !empty($record->description)),
                            ])
                            ->columns(1)
                            ->contained(true),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('enroll')
                ->label('Enroll in Program')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->size('lg')
                ->requiresConfirmation()
                ->modalHeading('Enroll in Training Program')
                ->modalDescription(fn() => "Are you sure you want to enroll in '{$this->record->name}'? This will add the program to your learning dashboard.")
                ->modalSubmitActionLabel('Enroll Now')
                ->action(function () {
                    try {
                        // Check if already enrolled (safety check)
                        $existingEnrollment = ProgramEnrollment::where('student_id', Auth::user()->id)
                            ->where('training_program_id', $this->record->id)
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
                            'training_program_id' => $this->record->id,
                            'enrolled_at' => now(),
                            'status' => ProgramEnrollmentStatus::ACTIVE->value,
                        ]);

                        Notification::make()
                            ->title('Enrollment Successful!')
                            ->body("You have successfully enrolled in '{$this->record->name}'. You can now access it from your programs.")
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
        ];
    }
}
