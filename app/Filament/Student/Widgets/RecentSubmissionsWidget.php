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
                    ->with(['task.section.trainingProgram', 'review']) // Add review relationship
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
                Tables\Columns\TextColumn::make('review.score')
                    ->label('Score')
                    ->formatStateUsing(fn ($state) => $state ? $state . '%' : 'Pending')
                    ->badge()
                    ->color(function ($state) {
                        if (!$state) return 'gray';
                        if ($state >= 90) return 'success';
                        if ($state >= 70) return 'warning';
                        return 'danger';
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
