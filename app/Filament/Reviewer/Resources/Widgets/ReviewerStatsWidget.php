<?php

namespace App\Filament\Reviewer\Resources\Widgets;

use App\Enums\SubmissionTypes;
use App\Models\Review;
use App\Models\Submission;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ReviewerStatsWidget extends BaseWidget
{
    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $reviewerId = Auth::id();

        $base = Submission::whereHas('review', fn ($q) => $q->where('reviewer_id', $reviewerId));

        $needsReview = (clone $base)->whereIn('status', [
            SubmissionTypes::PENDING_REVIEW->value,
            SubmissionTypes::UNDER_REVIEW->value,
        ])->count();

        $newToday = (clone $base)
            ->where('status', SubmissionTypes::PENDING_REVIEW->value)
            ->whereDate('submitted_at', today())
            ->count();

        $completedToday = Review::where('reviewer_id', $reviewerId)
            ->whereDate('reviewed_at', today())
            ->count();

        $completedTotal = Review::where('reviewer_id', $reviewerId)
            ->where('is_completed', true)
            ->count();

        $avgScore = Review::where('reviewer_id', $reviewerId)
            ->whereNotNull('score')
            ->where('is_completed', true)
            ->avg('score');

        $needsRevisionCount = (clone $base)
            ->where('status', SubmissionTypes::NEEDS_REVISION->value)
            ->count();

        return [
            Stat::make('In Your Queue', $needsReview)
                ->description($needsReview > 0
                    ? ($newToday > 0 ? "{$newToday} new today" : 'Ready when you are')
                    : 'Nothing waiting 🎉')
                ->descriptionIcon($needsReview > 0 ? 'heroicon-m-inbox-arrow-down' : 'heroicon-m-check-badge')
                ->color($needsReview > 0 ? 'warning' : 'success'),

            Stat::make('Reviewed Today', $completedToday)
                ->description($completedToday > 0 ? 'Keep up the momentum!' : 'None yet today')
                ->descriptionIcon('heroicon-m-bolt')
                ->color($completedToday > 0 ? 'success' : 'gray'),

            Stat::make('Total Completed', $completedTotal)
                ->description('All-time reviews completed')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary'),

            Stat::make('Awaiting Resubmission', $needsRevisionCount)
                ->description($needsRevisionCount > 0
                    ? 'Candidates notified by email'
                    : 'None pending')
                ->descriptionIcon('heroicon-m-envelope')
                ->color($needsRevisionCount > 0 ? 'warning' : 'gray'),
        ];
    }
}
