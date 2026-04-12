<?php

namespace App\Filament\Student\Widgets;

use App\Models\Submission;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentSubmissionsWidget extends BaseWidget
{
    protected static ?int    $sort        = 2;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading     = 'Recent Submissions';
    protected static ?string $description = 'Your latest submissions with status and scores';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Submission::query()
                    ->where('student_id', auth()->id())
                    ->with([
                        'task.section.trainingProgram',
                        'task.resultPublication',
                        'review:id,submission_id,score,is_completed',
                    ])
                    ->latest('submitted_at')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('task.section.trainingProgram.name')
                    ->label('Program')
                    ->badge()
                    ->color('info')
                    ->limit(20),

                Tables\Columns\TextColumn::make('task.title')
                    ->label('Task')
                    ->weight('bold')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->task?->title),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->since()
                    ->sortable(),

                Tables\Columns\ViewColumn::make('status')
                    ->label('Status')
                    ->view('filament.student.widgets.submission-status-compact'),

                Tables\Columns\TextColumn::make('review.score')
                    ->label('Score')
                    ->formatStateUsing(function ($state, $record) {
                        if (! $record->task?->resultPublication?->is_published) {
                            return 'Result Unpublished';
                        }
                        return $state !== null ? $state . ' / ' . $record->task->max_score : 'Not Scored';
                    })
                    ->badge()
                    ->color(function ($state, $record) {
                        if (! $record->task?->resultPublication?->is_published) {
                            return 'gray';
                        }

                        $reviewScore = $record->review?->score;
                        $maxScore    = $record->task?->max_score;

                        if ($reviewScore === null || ! $maxScore || $maxScore <= 0) {
                            return 'gray';
                        }

                        $pct = ($reviewScore / $maxScore) * 100;

                        return match (true) {
                            $pct >= 75 => 'success',
                            $pct >= 50 => 'warning',
                            default    => 'danger',
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.student.resources.results.view', $record))
                    ->visible(fn ($record) =>
                        $record->review?->score !== null &&
                        $record->task?->resultPublication?->is_published
                    ),
            ])
            ->paginated(false);
    }
}
