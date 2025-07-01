<?php

namespace App\Filament\Widgets;

use App\Enums\SubmissionTypes;
use App\Models\Submission;
use App\Models\Review;
use App\Models\User;
use App\Services\Utility\Constants;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class SubmissionAdminWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full'; // Width
    protected static bool $isLazy = true; // Lazy loading

    protected function getColumns(): int
    {
        return 4; // This sets 4 stat cards per row
    }

    protected function getHeading(): string
    {
        $total = Submission::count();
        return "Submission Overview ({$total} total)";
    }
    protected function getDescription(): ?string
    {
        return 'Real-time submission statistics and metrics';
    }

    protected function getStats(): array
    {
        // Get current period data
        $totalSubmissions = Submission::count();
        $pendingReview = Submission::where('status', SubmissionTypes::PENDING_REVIEW->value)->count();
        $underReview = Submission::where('status', SubmissionTypes::UNDER_REVIEW->value)->count();
        $completed = Submission::where('status', SubmissionTypes::COMPLETED->value)->count();
        $needsRevision = Submission::where('status', SubmissionTypes::NEEDS_REVISION->value)->count();
        $flagged = Submission::where('status', SubmissionTypes::FLAGGED->value)->count();

        // Get previous period for comparison
        $previousPeriodStart = Carbon::now()->subDays(30);
        $currentPeriodStart = Carbon::now()->subDays(15);

        $previousPeriodSubmissions = Submission::whereBetween('created_at', [$previousPeriodStart, $currentPeriodStart])->count();
        $currentPeriodSubmissions = Submission::where('created_at', '>=', $currentPeriodStart)->count();

        // Calculate percentage change
        $submissionChange = $previousPeriodSubmissions > 0
            ? (($currentPeriodSubmissions - $previousPeriodSubmissions) / $previousPeriodSubmissions) * 100
            : 0;

        // Get average review time
        $avgReviewTime = Review::whereNotNull('reviewed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, reviewed_at)) as avg_hours')
            ->first()
            ->avg_hours ?? 0;

        // Get active reviewers count
        $activeReviewers = User::where('role_id', Constants::REVIEWER_ID)
            ->where('is_active', true)
            ->count();

        // Get overdue submissions (older than 7 days without review)
        $overdueSubmissions = Submission::where('status', SubmissionTypes::PENDING_REVIEW->value)
            ->where('created_at', '<', Carbon::now()->subDays(7))
            ->count();

        // Get average score
        $avgScore = Review::whereNotNull('score')
            ->avg('score') ?? 0;

        // Get completion rate
        $completionRate = $totalSubmissions > 0
            ? ($completed / $totalSubmissions) * 100
            : 0;

        return [
            Stat::make('Total Submissions', $totalSubmissions)
                ->description($submissionChange > 0 ? "{$submissionChange}% increase" : "{$submissionChange}% decrease")
                ->descriptionIcon($submissionChange > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($submissionChange > 0 ? 'success' : 'danger')
                ->chart($this->getSubmissionChart()),

            Stat::make('Pending Review', $pendingReview)
                ->description($overdueSubmissions > 0 ? "{$overdueSubmissions} overdue" : 'On track')
                ->descriptionIcon($overdueSubmissions > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($overdueSubmissions > 0 ? 'warning' : 'success'),

            Stat::make('Under Review', $underReview)
                ->description("Avg: " . round($avgReviewTime, 1) . " hours")
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make('Completed', $completed)
                ->description(round($completionRate, 1) . "% completion rate")
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

    private function getSubmissionChart(): array
    {
        return Submission::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();
    }

    private function getScoreDescription(float $avgScore): string
    {
        return match (true) {
            $avgScore >= 8 => 'Excellent performance',
            $avgScore >= 6 => 'Good performance',
            $avgScore >= 4 => 'Fair performance',
            default => 'Needs improvement'
        };
    }

    private function getScoreColor(float $avgScore): string
    {
        return match (true) {
            $avgScore >= 7 => 'success',
            $avgScore >= 5 => 'warning',
            default => 'danger'
        };
    }

    private function getReviewerWorkload(int $reviewers, int $underReview): string
    {
        if ($reviewers == 0) return 'No active reviewers';

        $workloadPerReviewer = $underReview / $reviewers;

        return match (true) {
            $workloadPerReviewer > 10 => 'High workload',
            $workloadPerReviewer > 5 => 'Moderate workload',
            default => 'Light workload'
        };
    }
}
