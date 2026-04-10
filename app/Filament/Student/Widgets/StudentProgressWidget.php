<?php

namespace App\Filament\Student\Widgets;

use App\Models\Submission;
use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StudentProgressWidget extends BaseWidget
{
    protected static ?int    $sort             = 1;
    protected static ?string $pollingInterval  = '60s';

    protected function getStats(): array
    {
        $candidateId = auth()->id();

        // Total tasks across all enrolled programs
        $enrolledTasks = Task::whereHas('section.trainingProgram.enrollments', function ($q) use ($candidateId) {
            $q->where('student_id', $candidateId);
        })->count();

        // Tasks with at least one submission from this candidate
        $submittedTasks = Submission::where('student_id', $candidateId)->count();

        // FIX: Combined two near-identical queries into one.
        // Previously fired one COUNT query and one SELECT+get() query with identical
        // WHERE clauses — both filtering for graded+published submissions.
        // Now loads the collection once and derives both count and average from it.
        $gradedSubmissions = Submission::where('student_id', $candidateId)
            ->whereHas('review', fn ($q) => $q->whereNotNull('score'))
            ->whereHas('task.resultPublication', fn ($q) => $q->where('is_published', true))
            ->with(['review:id,submission_id,score'])
            ->get();

        $gradedCount  = $gradedSubmissions->count();
        $averageScore = $gradedSubmissions->avg('review.score');

        $completionRate = $enrolledTasks > 0
            ? round(($submittedTasks / $enrolledTasks) * 100)
            : 0;

        // FIX: Average score is a raw rubric score (e.g. 7.5 out of 10), NOT a
        // percentage. Displaying it with a '%' suffix was misleading. Display as
        // a decimal score and describe it relative to the 10-point scale.
        $scoreDisplay    = $averageScore ? round($averageScore, 1) . ' / 10' : 'N/A';
        $scoreDescription = match (true) {
            $averageScore === null     => 'No graded results yet',
            $averageScore >= 8.0       => 'Excellent performance!',
            $averageScore >= 6.0       => 'Good progress',
            $averageScore >= 5.0       => 'Meeting expectations',
            default                   => 'Needs improvement',
        };

        return [
            Stat::make('Total Tasks', $enrolledTasks)
                ->description('From enrolled programs')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info')
                ->chart([7, 12, 8, 15, 10, 18, $enrolledTasks]),

            Stat::make('Submitted Tasks', $submittedTasks)
                ->description("{$completionRate}% completion rate")
                ->descriptionIcon(
                    $completionRate >= 75
                        ? 'heroicon-m-arrow-trending-up'
                        : 'heroicon-m-arrow-trending-down'
                )
                ->color(match (true) {
                    $completionRate >= 75 => 'success',
                    $completionRate >= 50 => 'warning',
                    default               => 'danger',
                })
                ->chart([3, 7, 5, 12, 8, 15, $submittedTasks]),

            Stat::make('Graded Results', $gradedCount)
                ->description('Published results available')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success')
                ->chart([1, 3, 2, 7, 5, 12, $gradedCount]),

            Stat::make('Average Score', $scoreDisplay)
                ->description($scoreDescription)
                ->descriptionIcon(
                    $averageScore >= 8.0
                        ? 'heroicon-m-star'
                        : 'heroicon-m-chart-bar'
                )
                ->color(match (true) {
                    $averageScore === null => 'gray',
                    $averageScore >= 8.0   => 'success',
                    $averageScore >= 6.0   => 'warning',
                    default                => 'danger',
                })
                ->chart($averageScore ? [6.5, 7.2, 6.8, 7.8, 7.5, 8.2, round($averageScore, 1)] : []),
        ];
    }
}
