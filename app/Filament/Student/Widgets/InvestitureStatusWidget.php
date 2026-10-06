<?php

namespace App\Filament\Student\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use App\Models\Task;
use App\Models\Submission;

/**
 * Displays a prominent pass/fail status banner on the student dashboard.
 *
 * Rules:
 * - Hidden entirely when neither score is published yet.
 * - Shows portfolio-only status ("Qualified for interview" / "Not yet there")
 *   when portfolio results are available but interview score is still pending.
 * - Shows the final investiture verdict ("Congratulations!" / "Did not qualify")
 *   once both scores are published and cutoffs are configured.
 */
class InvestitureStatusWidget extends Widget
{
    protected static ?int $sort = 0; // appears above the stats cards

    protected static string $view = 'filament.student.widgets.investiture-status';

    protected int | string | array $columnSpan = 'full';

    /**
     * Data passed to the blade view.
     */
    public function getViewData(): array
    {
        $candidateId = auth()->id();

        // ── Programme config ───────────────────────────────────────────────
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

        if (! $program) {
            return ['show' => false];
        }

        $programId       = $program->program_id;
        $portfolioWeight = (float) $program->portfolio_weight;
        $interviewWeight = (float) $program->interview_weight;
        $portfolioCutoff = $program->portfolio_cutoff !== null ? (float) $program->portfolio_cutoff : null;
        $totalCutoff     = $program->total_cutoff     !== null ? (float) $program->total_cutoff     : null;

        // ── Portfolio score ────────────────────────────────────────────────
        $maxScore = $programId
            ? (float) Task::where('is_active', 1)
                ->whereNull('deleted_at')
                ->whereHas('section', fn ($q) => $q->where('training_program_id', $programId))
                ->sum('max_score')
            : 0;

        $submissionIds = Submission::where('student_id', $candidateId)->pluck('id');

        $portfolioRawScore = (float) DB::table('reviews')
            ->join('submissions', 'reviews.submission_id', '=', 'submissions.id')
            ->join('tasks', 'submissions.task_id', '=', 'tasks.id')
            ->join('result_publications', 'tasks.id', '=', 'result_publications.task_id')
            ->whereIn('reviews.submission_id', $submissionIds)
            ->where('reviews.is_completed', true)
            ->where('result_publications.is_published', true)
            ->sum(DB::raw('CAST(reviews.score AS DECIMAL(10,2))'));

        $portfolioPublished = $portfolioRawScore > 0 || $gradedCount = DB::table('reviews')
                    ->join('submissions', 'reviews.submission_id', '=', 'submissions.id')
                    ->join('tasks', 'submissions.task_id', '=', 'tasks.id')
                    ->join('result_publications', 'tasks.id', '=', 'result_publications.task_id')
                    ->whereIn('reviews.submission_id', $submissionIds)
                    ->where('reviews.is_completed', true)
                    ->where('result_publications.is_published', true)
                    ->count() > 0;

        $portfolioPercentage = $maxScore > 0
            ? round(($portfolioRawScore / $maxScore) * 100, 1)
            : 0;

        $portfolioContribution = round($portfolioPercentage * ($portfolioWeight / 100), 1);

        // ── Interview score ────────────────────────────────────────────────
        $candidate = DB::table('users')
            ->where('id', $candidateId)
            ->select(['interview_score', 'interview_score_published'])
            ->first();

        $interviewPublished = (bool) ($candidate?->interview_score_published ?? false);
        $interviewScoreRaw  = $candidate?->interview_score !== null ? (float) $candidate->interview_score : null;
        $interviewContribution = ($interviewPublished && $interviewScoreRaw !== null)
            ? round($interviewScoreRaw, 1)
            : null;

        // ── Nothing published yet — hide widget ───────────────────────────
        if (! $portfolioPublished && ! $interviewPublished) {
            return ['show' => false];
        }

        // ── Determine which phase to show ──────────────────────────────────

        // Phase 1: portfolio published, interview still pending
        if ($portfolioPublished && ! $interviewPublished) {
            // Only show a portfolio status if a cutoff is configured
            if ($portfolioCutoff === null) {
                return ['show' => false];
            }

            $qualifiesForInterview = $portfolioPercentage >= $portfolioCutoff;

            return [
                'show'    => true,
                'phase'   => 'portfolio',
                'passes'  => $qualifiesForInterview,
                'title'   => $qualifiesForInterview
                    ? 'You are qualified for interview!'
                    : 'Portfolio score below the interview cutoff',
                'message' => $qualifiesForInterview
                    ? "Your portfolio score of {$portfolioPercentage}% meets the required {$portfolioCutoff}% cutoff. Congratulations — you qualify for the interview stage!"
                    : "Your portfolio score of {$portfolioPercentage}% is below the required {$portfolioCutoff}% cutoff. Keep working hard!",
                'score'   => "{$portfolioPercentage}% portfolio (cutoff: {$portfolioCutoff}%)",
            ];
        }

        // Phase 2: both scores published — show final investiture verdict
        $totalScore = round($portfolioContribution + $interviewContribution, 1);

        if ($totalCutoff === null) {
            // Cutoff not set — show total without pass/fail label
            return [
                'show'    => true,
                'phase'   => 'total_no_cutoff',
                'passes'  => null,
                'title'   => 'All scores released',
                'message' => "Your combined score is {$totalScore}/100 (portfolio: {$portfolioContribution}/{$portfolioWeight}, interview: {$interviewContribution}/{$interviewWeight}).",
                'score'   => "{$totalScore}/100",
            ];
        }

        $qualifiesForInvestiture = $totalScore >= $totalCutoff;

        return [
            'show'    => true,
            'phase'   => 'final',
            'passes'  => $qualifiesForInvestiture,
            'title'   => $qualifiesForInvestiture
                ? 'Congratulations! You are qualified for investiture!'
                : 'You did not meet the investiture cutoff',
            'message' => $qualifiesForInvestiture
                ? "Your total score of {$totalScore}/100 meets the required {$totalCutoff}/100. You are officially qualified for investiture. Well done!"
                : "Your total score of {$totalScore}/100 is below the required {$totalCutoff}/100 cutoff. (Portfolio: {$portfolioContribution}/{$portfolioWeight}, Interview: {$interviewContribution}/{$interviewWeight})",
            'score'   => "{$totalScore}/100 (cutoff: {$totalCutoff}/100)",
        ];
    }
}
