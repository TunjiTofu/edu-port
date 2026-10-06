<?php

namespace App\Filament\Student\Widgets;

use App\Models\Submission;
use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StudentProgressWidget extends BaseWidget
{
    protected static ?int    $sort            = 1;
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $candidateId = auth()->id();

        // ── Enrolled programme & its scoring config ────────────────────────
        $program = DB::table('program_enrollments')
            ->join('training_programs', 'program_enrollments.training_program_id', '=', 'training_programs.id')
            ->where('program_enrollments.student_id', $candidateId)
            ->whereNull('program_enrollments.deleted_at')
            ->latest('program_enrollments.enrolled_at')
            ->select([
                'training_programs.id as program_id',
                'training_programs.portfolio_weight',
                'training_programs.interview_weight',
                'training_programs.portfolio_cutoff',
                'training_programs.total_cutoff',
            ])
            ->first();

        $programId       = $program?->program_id;
        $portfolioWeight = (float) ($program?->portfolio_weight ?? 60);
        $interviewWeight = (float) ($program?->interview_weight ?? 40);
        $portfolioCutoff = $program?->portfolio_cutoff !== null ? (float) $program->portfolio_cutoff : null;
        $totalCutoff     = $program?->total_cutoff     !== null ? (float) $program->total_cutoff     : null;

        // ── Tasks in the enrolled programme ───────────────────────────────
        $enrolledTasks = $programId
            ? Task::where('is_active', 1)
                ->whereNull('deleted_at')
                ->whereHas('section', fn ($q) => $q->where('training_program_id', $programId))
                ->count()
            : 0;

        $maxScore = $programId
            ? (float) Task::where('is_active', 1)
                ->whereNull('deleted_at')
                ->whereHas('section', fn ($q) => $q->where('training_program_id', $programId))
                ->sum('max_score')
            : 0;

        // ── Submission counts ──────────────────────────────────────────────
        $submittedTasks = Submission::where('student_id', $candidateId)->count();

        // ── Graded submissions (published results only) ────────────────────
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

        $scoreDisplay     = $averageScore ? round($averageScore, 1) . ' / 10' : 'N/A';
        $scoreDescription = match (true) {
            $averageScore === null => 'No graded results yet',
            $averageScore >= 8.0   => 'Excellent performance!',
            $averageScore >= 6.0   => 'Good progress',
            $averageScore >= 5.0   => 'Meeting expectations',
            default                => 'Needs improvement',
        };

        // ── Portfolio score (published reviews only) ───────────────────────
        $submissionIds = Submission::where('student_id', $candidateId)->pluck('id');

        $portfolioRawScore = (float) DB::table('reviews')
            ->join('submissions', 'reviews.submission_id', '=', 'submissions.id')
            ->join('tasks', 'submissions.task_id', '=', 'tasks.id')
            ->join('result_publications', 'tasks.id', '=', 'result_publications.task_id')
            ->whereIn('reviews.submission_id', $submissionIds)
            ->where('reviews.is_completed', true)
            ->where('result_publications.is_published', true)
            ->sum(DB::raw('CAST(reviews.score AS DECIMAL(10,2))'));

        // Portfolio as a percentage of its max possible score
        $portfolioPercentage = $maxScore > 0
            ? round(($portfolioRawScore / $maxScore) * 100, 1)
            : 0;

        // Portfolio contribution toward the 100-point total
        // e.g. 74.2% * 60 / 100 = 44.5 out of 60
        $portfolioContribution = round($portfolioPercentage * ($portfolioWeight / 100), 1);

        // ── Interview score (published by admin) ───────────────────────────
        $candidate = DB::table('users')
            ->where('id', $candidateId)
            ->select(['interview_score', 'interview_score_published'])
            ->first();

        $interviewPublished    = (bool) ($candidate?->interview_score_published ?? false);
        $interviewScoreRaw     = $candidate?->interview_score !== null ? (float) $candidate->interview_score : null;

        // Interview contribution toward the 100-point total
        // e.g. 75% * 40 / 100 = 30 out of 40
        $interviewContribution = ($interviewPublished && $interviewScoreRaw !== null)
            ? round($interviewScoreRaw, 1)
            : null;

        // ── Combined total (only when both scores are published) ───────────
        $totalScore = ($interviewContribution !== null)
            ? round($portfolioContribution + $interviewContribution, 1)
            : null;

        // ── Colour helpers ─────────────────────────────────────────────────

        // Portfolio colour: against cutoff if set, otherwise generic thresholds
        $portfolioColor = match (true) {
            $portfolioCutoff !== null && $portfolioPercentage >= $portfolioCutoff => 'success',
            $portfolioCutoff !== null                                              => 'danger',
            $portfolioPercentage >= 75                                             => 'success',
            $portfolioPercentage >= 50                                             => 'warning',
            $portfolioPercentage > 0                                               => 'danger',
            default                                                                => 'gray',
        };

        $portfolioIcon = match ($portfolioColor) {
            'success' => 'heroicon-m-arrow-trending-up',
            'warning' => 'heroicon-m-minus',
            default   => 'heroicon-m-arrow-trending-down',
        };

        // Total colour: against cutoff if set, otherwise generic thresholds
        $totalColor = 'gray';
        if ($totalScore !== null) {
            $totalColor = match (true) {
                $totalCutoff !== null && $totalScore >= $totalCutoff => 'success',
                $totalCutoff !== null                                => 'danger',
                $totalScore >= 75                                    => 'success',
                $totalScore >= 50                                    => 'warning',
                default                                              => 'danger',
            };
        }

        // Portfolio cutoff description suffix
        $portfolioCutoffHint = $portfolioCutoff !== null
            ? " (cutoff: {$portfolioCutoff}%)"
            : '';

        $totalCutoffHint = $totalCutoff !== null
            ? " (cutoff: {$totalCutoff}/100)"
            : '';

        return [
            // ── Existing cards ─────────────────────────────────────────────
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

            // ── Portfolio score cards ──────────────────────────────────────
            Stat::make(
                'Portfolio Score',
                number_format($portfolioRawScore, 1) . ' / ' . number_format($maxScore, 0)
            )
                ->description('Earned score from portfolio submission' . $portfolioCutoffHint)
                ->descriptionIcon($portfolioIcon)
                ->color($portfolioColor)
                ->icon('heroicon-m-document-text'),

            Stat::make(
                'Portfolio %',
                $portfolioPercentage . '%'
            )
                ->description('Portfolio as a percentage' . $portfolioCutoffHint)
                ->descriptionIcon($portfolioIcon)
                ->color($portfolioColor)
                ->icon('heroicon-m-chart-bar-square'),

            Stat::make(
                'Portfolio /' . (int) $portfolioWeight,
                $portfolioContribution . ' / ' . (int) $portfolioWeight
            )
                ->description("Score scaled to {$portfolioWeight}-point weight" . $portfolioCutoffHint)
                ->descriptionIcon($portfolioIcon)
                ->color($portfolioColor)
                ->icon('heroicon-m-trophy'),

            // ── Interview score card ───────────────────────────────────────
            Stat::make(
                'Interview /' . (int) $interviewWeight,
                $interviewContribution !== null
                    ? $interviewContribution . ' / ' . (int) $interviewWeight
                    : 'Pending'
            )
                ->description(
                    $interviewContribution !== null
                        ? "Interview score: {$interviewScoreRaw}% scaled to /{$interviewWeight}"
                        : 'Interview score not yet released'
                )
                ->descriptionIcon(
                    $interviewContribution !== null
                        ? 'heroicon-m-check-circle'
                        : 'heroicon-m-clock'
                )
                ->color(
                    $interviewContribution !== null
                        ? ($interviewContribution >= ($interviewWeight * 0.75) ? 'success'
                        : ($interviewContribution >= ($interviewWeight * 0.5) ? 'warning' : 'danger'))
                        : 'gray'
                )
                ->icon('heroicon-m-microphone'),

            // ── Combined total ─────────────────────────────────────────────
            Stat::make(
                'Total Score /100',
                $totalScore !== null ? $totalScore . ' / 100' : 'Pending'
            )
                ->description(
                    $totalScore !== null
                        ? 'Portfolio + Interview combined' . $totalCutoffHint
                        : 'Available once interview score is released'
                )
                ->descriptionIcon(
                    $totalScore !== null
                        ? ($totalColor === 'success' ? 'heroicon-m-star' : 'heroicon-m-minus')
                        : 'heroicon-m-clock'
                )
                ->color($totalColor)
                ->icon('heroicon-m-academic-cap'),
        ];
    }
}
