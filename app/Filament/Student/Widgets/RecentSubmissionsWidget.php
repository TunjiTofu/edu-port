<?php

namespace App\Filament\Student\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Builder;

class RecentSubmissionsWidget extends BaseWidget // Missing extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Recent Submissions';
    protected static ?string $description = 'Your last 5 submissions with status and scores';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Submission::query()
                    ->where('student_id', auth()->id())
                    ->with(['task.section.trainingProgram', 'task.resultPublication','review']) // Add review relationship
                    ->latest('submitted_at')
                    ->limit(3)
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
                    ->tooltip(fn ($record) => $record->task->title),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->since()
                    ->sortable(),

                Tables\Columns\ViewColumn::make('status')
                    ->label('Status')
                    ->view('filament.student.widgets.submission-status-compact'),

                // Updated score column to handle your review relationship
//                Tables\Columns\TextColumn::make('review.score')
//                    ->label('Score')
//                    ->formatStateUsing(function ($state, $record) {
//                        if ($record->task->resultPublication->is_published) {
//                            return $state ?? 'Not Scored';
//                        }
//                        return 'Result Unpublished';
//                    })
//                    ->badge()
//                    ->color(function ($state, $record) {
//                        $maxScore = $record->task->max_score;
//                        $score = $record->score;
//                        $scorePercentage = ($score / $maxScore) * 100;
//
//                        if (!$scorePercentage) return 'gray';
//                        if ($scorePercentage >= 75) return 'success';
//                        if ($scorePercentage >= 50) return 'warning';
//                        return 'danger';
//                    }),


                Tables\Columns\TextColumn::make('review.score')
                    ->label('Score')
                    ->formatStateUsing(function ($state, $record) {
                        // Check if results are published
                        if ($record->task->resultPublication->is_published) {
                            return $state ?? 'Not Scored'; // Handle null scores
                        }
                        return 'Result Unpublished';
                    })
                    ->badge()
                    ->color(function ($state, $record) {
                        $maxScore = $record->task->max_score;
                        $score = $record->score;
                        $scorePercentage = ($score / $maxScore) * 100;
                        if (!$scorePercentage) return 'danger';
                        if ($scorePercentage >= 75 && $record->task->resultPublication->is_published) return 'success';
                        if ($scorePercentage >= 50 && $record->task->resultPublication->is_published) return 'warning';
                        return 'gray';
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.student.resources.results.view', $record))
                    ->visible(function ($record) {
                        return $record->review &&
                            $record->review->score !== null &&
                            $record->task->resultPublication &&
                            $record->task->resultPublication->is_published;
                    }),
            ])
            ->paginated(false);
    }
}
