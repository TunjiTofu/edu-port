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

        $totalScore = Task::whereHas('section.trainingProgram.enrollments', function ($query) use ($studentId) {
            $query->where('student_id', $studentId);
        })->sum('max_score');


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
        $studentScore = $submissions->sum('review.score');

        // Convert to percentages
        $scoreOver100 = $studentScore > 0 ? ($studentScore / $totalScore) * 100 : 0;
        $scoreOver60 = $studentScore > 0 ? ($studentScore / $totalScore) * 60 : 0;

        // Round values
        $scoreOver100Rounded = round($scoreOver100, 1);
        $scoreOver60Rounded  = round($scoreOver60, 1);
        $studentScoreRounded = round($studentScore, 1);

        // Define description and color based on % score
        if ($scoreOver100 >= 70) {
            $description = 'Excellent performance!';
            $icon = 'heroicon-m-star';
            $color = 'success';
        } elseif ($scoreOver100 >= 50) {
            $description = 'Good progress';
            $icon = 'heroicon-m-chart-bar';
            $color = 'warning';
        } else {
            $description = 'Needs improvement';
            $icon = 'heroicon-m-exclamation-triangle';
            $color = 'danger';
        }


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

//            Stat::make('Average Score', $averageScore ? round($averageScore, 1) : 'N/A')
//                ->description($averageScore >= 8 ? 'Excellent performance!' : ($averageScore >= 7 ? 'Good progress' : 'Needs improvement'))
//                ->descriptionIcon($averageScore >= 8 ? 'heroicon-m-star' : 'heroicon-m-chart-bar')
//                ->color($averageScore >= 8 ? 'success' : ($averageScore >= 7 ? 'warning' : 'danger'))
//                ->chart($averageScore ? [65, 72, 68, 78, 75, 82, round($averageScore)] : []),

//            dd([
//                'student_score' => $studentScore,
//                'total_score' => $totalScore,
//                'score_over_100' => $scoreOver100,
//                'score_over_60' => $scoreOver60
//            ]);



//        Stat::make('Student Score', $studentScore ? round($studentScore, 1) . '/' . $totalScore : 'N/A')
//                ->description($studentScore >= 8 ? 'Excellent performance!' : ($studentScore >= 70 ? 'Good progress' : 'Needs improvement'))
//                ->descriptionIcon($studentScore >= 80 ? 'heroicon-m-star' : 'heroicon-m-chart-bar')
//                ->color($studentScore >= 80 ? 'success' : ($studentScore >= 70 ? 'warning' : 'danger'))
//                ->chart($studentScore ? [65, 72, 68, 78, 75, 82, round($studentScore)] : []),

            // Raw score stat (student / total)
            Stat::make('Student Score', $studentScore ? "{$studentScoreRounded}/{$totalScore}" : 'N/A')
                ->description($description)
                ->descriptionIcon($icon)
                ->color($color)
                ->chart($studentScore ? [65, 72, 68, 78, 75, 82, $scoreOver100Rounded] : []),

            // Score over 100
            Stat::make('Score /100', $studentScore ? "{$scoreOver100Rounded}/100" : 'N/A')
                ->description('Normalized to 100 scale')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($color)
                ->chart($studentScore ? [60, 70, 73, 80, 78, 85, $scoreOver100Rounded] : []),

            // Score over 60
            Stat::make('Score /60', $studentScore ? "{$scoreOver60Rounded}/60" : 'N/A')
                ->description('Normalized to 60 scale')
                ->descriptionIcon('heroicon-m-adjustments-horizontal')
                ->color($color)
                ->chart($studentScore ? [40, 45, 50, 55, 52, 58, $scoreOver60Rounded] : []),

        ];
    }
}
