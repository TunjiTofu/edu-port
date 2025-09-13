<?php

namespace App\Filament\Widgets;

use App\Enums\SubmissionTypes;
use App\Models\Submission;
use App\Models\Review;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SubmissionChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Submissions Overview';
    protected static ?int $sort = 4;
    protected static ?string $pollingInterval = '60s';

    public ?string $filter = 'week';

    protected int | string | array $columnSpan = 'full';
    protected static bool $isLazy = true; // Lazy loading
    protected static ?string $maxHeight = '200px';

    protected function getColumns(): int
    {
        return 3; // This sets 4 stat cards per row
    }

    protected function getFilters(): ?array
    {
        return [
            'week' => 'Last 7 days',
            'month' => 'Last 30 days',
            'quarter' => 'Last 3 months',
        ];
    }

    protected function getData(): array
    {
        $filter = $this->filter;

        // Determine date range based on filter
        $days = match ($filter) {
            'week' => 7,
            'month' => 30,
            'quarter' => 90,
            default => 7,
        };

        $dateFrom = Carbon::now()->subDays($days);

        // Get submission data grouped by date and status
        $submissionData = Submission::select([
            'status',
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        ])
            ->where('created_at', '>=', $dateFrom)
            ->groupBy(['status', 'date'])
            ->orderBy('date')
            ->get()
            ->groupBy('date');

        // Create date range
        $dates = [];
        $current = $dateFrom->copy();
        while ($current <= Carbon::now()) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        // Initialize data arrays
        $pendingData = [];
        $underReviewData = [];
        $completedData = [];
        $needsRevisionData = [];
        $flaggedData = [];

        // Fill data for each date
        foreach ($dates as $date) {
            $dayData = $submissionData->get($date, collect());

            $pendingData[] = $dayData->where('status', SubmissionTypes::PENDING_REVIEW->value)->sum('count');
            $underReviewData[] = $dayData->where('status', SubmissionTypes::UNDER_REVIEW->value)->sum('count');
            $completedData[] = $dayData->where('status', SubmissionTypes::COMPLETED->value)->sum('count');
            $needsRevisionData[] = $dayData->where('status', SubmissionTypes::NEEDS_REVISION->value)->sum('count');
            $flaggedData[] = $dayData->where('status', SubmissionTypes::FLAGGED->value)->sum('count');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pending Review',
                    'data' => $pendingData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'fill' => true,
                ],
                [
                    'label' => 'Under Review',
                    'data' => $underReviewData,
                    'backgroundColor' => 'rgba(168, 85, 247, 0.5)',
                    'borderColor' => 'rgb(168, 85, 247)',
                    'fill' => true,
                ],
                [
                    'label' => 'Completed',
                    'data' => $completedData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.5)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'fill' => true,
                ],
                [
                    'label' => 'Needs Revision',
                    'data' => $needsRevisionData,
                    'backgroundColor' => 'rgba(251, 191, 36, 0.5)',
                    'borderColor' => 'rgb(251, 191, 36)',
                    'fill' => true,
                ],
                [
                    'label' => 'Flagged',
                    'data' => $flaggedData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.5)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'fill' => true,
                ],
            ],
            'labels' => array_map(function ($date) use ($filter) {
                $carbon = Carbon::parse($date);
                return match ($filter) {
                    'week' => $carbon->format('M j'),
                    'month' => $carbon->format('M j'),
                    'quarter' => $carbon->format('M j'),
                    default => $carbon->format('M j'),
                };
            }, $dates),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
