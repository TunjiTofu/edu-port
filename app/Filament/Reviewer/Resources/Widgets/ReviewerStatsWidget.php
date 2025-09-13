<?php

namespace App\Filament\Reviewer\Resources\Widgets;

use App\Models\Submission;
use App\Models\Review;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReviewerStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $reviewerId = $user->id;

//        $totalAssigned = Submission::whereHas('student', function ($query) use ($user) {
//            $query->where('district_id', '!=', $user->district_id);
//        })->count();

        $totalAssigned = Review::where('reviewer_id', $reviewerId)->count();
//        $totalReviewed = Review::where('reviewer_id', $reviewerId)->count();

        $completed = Review::where('reviewer_id', $reviewerId)
            ->where('is_completed', true)
            ->count();

        $pending = $totalAssigned - $completed;

        return [
            Stat::make('Total Assigned', $totalAssigned)
                ->description('Submissions available for review')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

//            Stat::make('Reviewed', $totalReviewed)
//                ->description('Submissions you have reviewed')
//                ->descriptionIcon('heroicon-m-eye')
//                ->color('success'),

            Stat::make('Completed', $completed)
                ->description('Marked as completed')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Pending', $pending)
                ->description('Awaiting your review')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
