<?php

namespace App\Filament\Widgets;

use App\Enums\SubmissionTypes;
use App\Filament\Widgets\Concerns\FiltersYearByEnrollment;
use App\Models\Review;
use App\Models\Submission;
use App\Models\User;
use App\Services\Utility\Constants;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class SubmissionAdminWidget extends BaseWidget
{
    use FiltersYearByEnrollment;

    protected static ?string $pollingInterval = '30s';
    protected static ?int    $sort            = 3;
    protected int|string|array $columnSpan    = 'full';
    protected static bool $isLazy             = true;

    protected function getColumns(): int { return 4; }

    protected function getHeading(): string
    {
        $year  = $this->getSelectedYear();
        $label = $year ? " — {$year}" : '';
        $total = $this->baseSubmissionQuery()->count();
        return "Submission Overview{$label} ({$total} total)";
    }

    protected function getDescription(): ?string
    {
        return 'Real-time submission statistics filtered by the selected year.';
    }

    protected function getStats(): array
    {
        $year  = $this->getSelectedYear();
        $base  = $this->baseSubmissionQuery();

        $totalSubmissions = (clone $base)->count();
        $pendingReview    = (clone $base)->where('status', SubmissionTypes::PENDING_REVIEW->value)->count();
        $underReview      = (clone $base)->where('status', SubmissionTypes::UNDER_REVIEW->value)->count();
        $completed        = (clone $base)->where('status', SubmissionTypes::COMPLETED->value)->count();
        $needsRevision    = (clone $base)->where('status', SubmissionTypes::NEEDS_REVISION->value)->count();
        $flagged          = (clone $base)->where('status', SubmissionTypes::FLAGGED->value)->count();

        $overdueSubmissions = (clone $base)
            ->where('status', SubmissionTypes::PENDING_REVIEW->value)
            ->where('created_at', '<', Carbon::now()->subDays(7))
            ->count();

        // 30-day trend
        $previousPeriodStart      = Carbon::now()->subDays(30);
        $currentPeriodStart       = Carbon::now()->subDays(15);
        $previousPeriodSubmissions = (clone $base)->whereBetween('created_at', [$previousPeriodStart, $currentPeriodStart])->count();
        $currentPeriodSubmissions  = (clone $base)->where('created_at', '>=', $currentPeriodStart)->count();

        $submissionChange = $previousPeriodSubmissions > 0
            ? (($currentPeriodSubmissions - $previousPeriodSubmissions) / $previousPeriodSubmissions) * 100
            : 0;

        $avgScore = Review::whereNotNull('score')->avg('score') ?? 0;

        $avgReviewTime = Review::whereNotNull('reviewed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, reviewed_at)) as avg_hours')
            ->first()?->avg_hours ?? 0;

        $activeReviewers = User::where('role_id', Constants::REVIEWER_ID)
            ->where('is_active', true)
            ->count();

        $completionRate = $totalSubmissions > 0
            ? ($completed / $totalSubmissions) * 100
            : 0;

        return [
            Stat::make('Total Submissions', $totalSubmissions)
                ->description($submissionChange >= 0
                    ? round($submissionChange, 1) . '% increase (15d)'
                    : round(abs($submissionChange), 1) . '% decrease (15d)')
                ->descriptionIcon($submissionChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($submissionChange >= 0 ? 'success' : 'danger')
                ->chart($this->getSubmissionChart($year)),

            Stat::make('Pending Review', $pendingReview)
                ->description($overdueSubmissions > 0 ? "{$overdueSubmissions} overdue >7 days" : 'On track')
                ->descriptionIcon($overdueSubmissions > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($overdueSubmissions > 0 ? 'warning' : 'success'),

            Stat::make('Under Review', $underReview)
                ->description('Avg: ' . round($avgReviewTime, 1) . ' hours')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make('Completed', $completed)
                ->description(round($completionRate, 1) . '% completion rate')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Needs Revision', $needsRevision)
                ->description($needsRevision > 0 ? 'Requires attention' : 'All clear')
                ->descriptionIcon($needsRevision > 0 ? 'heroicon-m-arrow-path' : 'heroicon-m-check-circle')
                ->color($needsRevision > 0 ? 'warning' : 'success'),

            Stat::make('Flagged', $flagged)
                ->description($flagged > 0 ? 'Needs investigation' : 'No issues')
                ->descriptionIcon($flagged > 0 ? 'heroicon-m-flag' : 'heroicon-m-shield-check')
                ->color($flagged > 0 ? 'danger' : 'success'),

            Stat::make('Average Score', round($avgScore, 1) . '/10')
                ->description($this->getScoreDescription($avgScore))
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color($this->getScoreColor($avgScore)),

            Stat::make('Active Reviewers', $activeReviewers)
                ->description($this->getReviewerWorkload($activeReviewers, $underReview))
                ->descriptionIcon('heroicon-m-users')
                ->color($activeReviewers > 0 ? 'success' : 'danger'),
        ];
    }

    /**
     * Base query scoped by the selected year.
     * Called with clone() so we can reuse the same base for each stat.
     */
    private function baseSubmissionQuery()
    {
        $query = Submission::query();
        return $this->scopeSubmissionsByYear($query);
    }

    private function getSubmissionChart(?int $year): array
    {
        $query = Submission::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', Carbon::now()->subDays(7));

        $this->scopeSubmissionsByYear($query, $year);

        return $query->groupBy('date')->orderBy('date')->pluck('count')->toArray();
    }

    private function getScoreDescription(float $avgScore): string
    {
        return match (true) {
            $avgScore >= 8 => 'Excellent performance',
            $avgScore >= 6 => 'Good performance',
            $avgScore >= 4 => 'Fair performance',
            default        => 'Needs improvement',
        };
    }

    private function getScoreColor(float $avgScore): string
    {
        return match (true) {
            $avgScore >= 7 => 'success',
            $avgScore >= 5 => 'warning',
            default        => 'danger',
        };
    }

    private function getReviewerWorkload(int $reviewers, int $underReview): string
    {
        if ($reviewers === 0) return 'No active reviewers';
        $load = $underReview / $reviewers;
        return match (true) {
            $load > 10 => 'High workload',
            $load > 5  => 'Moderate workload',
            default    => 'Light workload',
        };
    }
}
