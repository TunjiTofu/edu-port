<?php

namespace App\Filament\Student\Resources\TrainingProgramResource\Pages;

use App\Enums\SubmissionTypes;
use App\Filament\Student\Resources\TrainingProgramResource;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewTrainingProgram extends ViewRecord
{
    protected static string $resource = TrainingProgramResource::class;

    // No EditAction — candidates are read-only on training programs
    protected function getHeaderActions(): array { return []; }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // ── Program Overview ─────────────────────────────────────────
                Section::make()
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 5])
                            ->schema([
                                // FIX: Small image — 80px circle on the left, not half the layout
                                ImageEntry::make('image_url')
                                    ->label('')
                                    ->circular()
                                    ->size(80)
                                    ->defaultImageUrl(asset('images/logo.png'))
                                    ->columnSpan(1)
                                    ->extraImgAttributes([
                                        'class' => 'ring-4 ring-primary-500/20 shadow mx-auto sm:mx-0',
                                    ]),

                                Group::make([
                                    TextEntry::make('name')
                                        ->label('')
                                        ->size('lg')
                                        ->weight('bold'),

                                    TextEntry::make('description')
                                        ->label('')
                                        ->prose()
                                        ->columnSpanFull(),
                                ])->columnSpan(['default' => 1, 'sm' => 4]),
                            ]),
                    ]),

                // ── Key Dates ─────────────────────────────────────────────────
                Section::make('Program Timeline')
                    ->icon('heroicon-o-calendar')
                    ->columns(['default' => 2, 'sm' => 4])
                    ->schema([
                        TextEntry::make('enrollments.enrolled_at')
                            ->label('Date Enrolled')
                            ->date('M j, Y')
                            ->badge()->color('success'),

                        TextEntry::make('start_date')
                            ->label('Starts')
                            ->date('M j, Y')
                            ->badge()->color('info'),

                        TextEntry::make('end_date')
                            ->label('Ends')
                            ->date('M j, Y')
                            ->badge()->color('warning'),

                        TextEntry::make('duration_weeks')
                            ->label('Duration')
                            ->badge()->color('gray'),
                    ]),

                // ── Program Structure ─────────────────────────────────────────
                Section::make('Program Content')
                    ->icon('heroicon-o-book-open')
                    ->schema([
                        RepeatableEntry::make('sections')
                            ->label('')
                            ->schema([
                                // Section header
                                TextEntry::make('name')
                                    ->label('')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->columnSpanFull(),

                                TextEntry::make('description')
                                    ->label('')
                                    ->prose()
                                    ->columnSpanFull()
                                    ->visible(fn ($record) => ! empty($record->description)),

                                // Tasks within the section
                                RepeatableEntry::make('tasks')
                                    ->label('Tasks')
                                    ->schema([
                                        Grid::make(['default' => 1, 'sm' => 4])
                                            ->schema([
                                                TextEntry::make('title')
                                                    ->label('Task')
                                                    ->weight('medium')
                                                    ->columnSpan(['default' => 1, 'sm' => 2]),

                                                TextEntry::make('due_date')
                                                    ->label('Due')
                                                    ->date('M j, Y')
                                                    ->color(fn ($state) =>
                                                    $state && $state->isPast() ? 'danger' : 'success'
                                                    ),

                                                TextEntry::make('submission_status')
                                                    ->label('Status')
                                                    ->badge()
                                                    ->state(function ($record) {
                                                        return $record->submissions
                                                            ->where('student_id', Auth::id())
                                                            ->first()
                                                            ?->status
                                                            ?? 'not_submitted';
                                                    })
                                                    ->color(fn ($state) => match ($state) {
                                                        SubmissionTypes::COMPLETED->value     => 'success',
                                                        SubmissionTypes::PENDING_REVIEW->value => 'info',
                                                        SubmissionTypes::UNDER_REVIEW->value  => 'warning',
                                                        SubmissionTypes::NEEDS_REVISION->value => 'danger',
                                                        SubmissionTypes::FLAGGED->value       => 'danger',
                                                        'not_submitted'                        => 'gray',
                                                        default                                => 'gray',
                                                    })
                                                    ->formatStateUsing(fn ($state) => match ($state) {
                                                        SubmissionTypes::COMPLETED->value      => '✅ Completed',
                                                        SubmissionTypes::PENDING_REVIEW->value => '⏳ Awaiting Review',
                                                        SubmissionTypes::UNDER_REVIEW->value   => '🔍 Under Review',
                                                        SubmissionTypes::NEEDS_REVISION->value => '⚠️ Needs Revision',
                                                        SubmissionTypes::FLAGGED->value        => '🚩 Flagged',
                                                        'not_submitted'                         => '📝 Not Submitted',
                                                        default                                 => ucfirst(str_replace('_', ' ', $state)),
                                                    }),
                                            ]),
                                    ])
                                    ->contained(false)
                                    ->columnSpanFull(),
                            ])
                            ->contained(true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Load only what this view needs — submissions scoped to this candidate.
     */
    protected function resolveRecord($key): \App\Models\TrainingProgram
    {
        return \App\Models\TrainingProgram::with([
            'sections.tasks.submissions' => fn ($q) => $q->where('student_id', Auth::id()),
            'enrollments'                => fn ($q) => $q->where('student_id', Auth::id()),
        ])->findOrFail($key);
    }
}
