<?php

namespace App\Filament\Student\Resources\TrainingProgramResource\Pages;

use App\Enums\SubmissionTypes;
use App\Filament\Student\Resources\TrainingProgramResource;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewTrainingProgram extends ViewRecord
{
    protected static string $resource = TrainingProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Program Overview')
                    ->schema([
                        Split::make([
                            Grid::make(2)
                                ->schema([
                                    ImageEntry::make('thumbnail')
                                        ->hiddenLabel()
                                        ->size(200),

                                    Group::make([
                                        TextEntry::make('name')
                                            ->size('lg')
                                            ->weight('bold'),

                                        TextEntry::make('description')
                                            ->prose(),

                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('start_date')
                                                    ->label('Program Start Date')
                                                    ->date()
                                                    ->icon('heroicon-o-calendar'),

                                                TextEntry::make('end_date')
                                                    ->label('Program End Date')
                                                    ->date()
                                                    ->icon('heroicon-o-calendar'),

                                                TextEntry::make('duration')
                                                    ->label('Duration')
                                                    ->icon('heroicon-o-clock')
                                                    ->state(function ($record) {
                                                        if (!$record->start_date || !$record->end_date) {
                                                            return 'N/A';
                                                        }

                                                        $start = Carbon::parse($record->start_date);
                                                        $end = Carbon::parse($record->end_date);

                                                        return $start->diffForHumans($end, [
                                                            'parts' => 3,
                                                            'join' => true,
                                                        ]);
                                                    }),

                                                TextEntry::make('enrollments.enrolled_at')
                                                    ->label('Date Enrolled')
                                                    ->date()
                                                    ->icon('heroicon-o-calendar'),
                                            ]),
                                    ]),
                                ]),
                        ]),
                    ]),

                Section::make('Program Structure')
                    ->schema([
                        RepeatableEntry::make('sections')
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Section Name')
                                    ->size('lg')
                                    ->weight('bold'),


                                TextEntry::make('description')
                                    ->prose(),

                                RepeatableEntry::make('tasks')
                                    ->schema([
                                        Grid::make(4)
                                            ->schema([
                                                TextEntry::make('id')
                                                    ->weight('medium'),

                                                TextEntry::make('title')
                                                    ->weight('medium'),

                                                TextEntry::make('due_date')
                                                    ->date()
                                                    ->color(fn($state) => $state && $state->isPast() ? 'danger' : 'success'),

                                                TextEntry::make('submission_status')
                                                    ->badge()
                                                    ->state(function ($record) {
                                                        $status = $record->submissions
                                                            ->where('student_id', Auth::id())
                                                            ->first()?->status ?? SubmissionTypes::PENDING_REVIEW->value;

                                                        return $status;
                                                    })
                                                    ->color(fn($state) => match ($state) {
                                                        SubmissionTypes::PENDING_REVIEW->value => 'gray',
                                                        SubmissionTypes::UNDER_REVIEW->value => 'info',
                                                        SubmissionTypes::NEEDS_REVISION->value => 'warning',
                                                        SubmissionTypes::COMPLETED->value => 'primary',
                                                        SubmissionTypes::FLAGGED->value => 'danger',
                                                        default => 'gray',
                                                    })
                                                    ->formatStateUsing(fn($state) => str($state)->title())
                                            ]),
                                    ])
                                    ->columns(1),
                            ])
                            ->columns(1),
                    ]),
            ]);
    }
}
