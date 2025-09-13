<?php

namespace App\Filament\Student\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Task;
use App\Models\Submission;
use Illuminate\Support\Facades\DB;

class StudentProgressWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $studentId = auth()->id();

        // Get all tasks from enrolled programs
        $enrolledTasks = Task::whereHas('section.trainingProgram.enrollments', function ($query) use ($studentId) {
            $query->where('student_id', $studentId);
        })->count();

        // Get completed tasks (submitted)
        $completedTasks = Submission::where('student_id', $studentId)
            ->whereHas('task.section.trainingProgram.enrollments', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->count();

        // Get graded submissions
        $gradedSubmissions = Submission::where('student_id', $studentId)
            ->whereHas('review', function ($query) {
                $query->whereNotNull('score');
            })
            ->whereHas('task', function ($query) {
                $query->whereHas('resultPublication', function ($subQuery) {
                    $subQuery->where('is_published', true);
                });
            })
            ->count();

        $submissions = Submission::where('student_id', $studentId)
            ->whereHas('review', function ($query) {
                $query->whereNotNull('score');
            })
            ->whereHas('task', function ($query) {
                $query->whereHas('resultPublication', function ($subQuery) {
                    $subQuery->where('is_published', true);
                });
            })
            ->with('review')
            ->get();

        $averageScore = $submissions->avg('review.score');

        $completionRate = $enrolledTasks > 0 ? round(($completedTasks / $enrolledTasks) * 100) : 0;

        return [
            Stat::make('Total Tasks', $enrolledTasks)
                ->description('From enrolled programs')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info')
                ->chart([7, 12, 8, 15, 10, 18, $enrolledTasks]),

            Stat::make('Completed Tasks', $completedTasks)
                ->description("$completionRate% completion rate")
                ->descriptionIcon($completionRate >= 75 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($completionRate >= 75 ? 'success' : ($completionRate >= 50 ? 'warning' : 'danger'))
                ->chart([3, 7, 5, 12, 8, 15, $completedTasks]),

            Stat::make('Graded Results', $gradedSubmissions)
                ->description('Published results')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success')
                ->chart([1, 3, 2, 7, 5, 12, $gradedSubmissions]),

            Stat::make('Average Score', $averageScore ? round($averageScore, 1) . '%' : 'N/A')
                ->description($averageScore >= 80 ? 'Excellent performance!' : ($averageScore >= 70 ? 'Good progress' : 'Needs improvement'))
                ->descriptionIcon($averageScore >= 80 ? 'heroicon-m-star' : 'heroicon-m-chart-bar')
                ->color($averageScore >= 80 ? 'success' : ($averageScore >= 70 ? 'warning' : 'danger'))
                ->chart($averageScore ? [65, 72, 68, 78, 75, 82, round($averageScore)] : []),
        ];
    }
}
