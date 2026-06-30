<?php

namespace App\Filament\Widgets;

use App\Enums\SubmissionTypes;
use App\Filament\Widgets\Concerns\FiltersYearByEnrollment;
use App\Models\Submission;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SubmissionChartWidget extends ChartWidget
{
    use FiltersYearByEnrollment;

    protected static ?int    $sort            = 4;
//    protected static ?string $pollingInterval = '60s';
    protected int|string|array $columnSpan    = 'full';
    protected static bool    $isLazy          = true;
    protected static ?string $maxHeight       = '200px';

    public ?string $filter = null;

    protected function getFilters(): ?array
    {
        return static::widgetFilterOptions();
    }

    public function getHeading(): string
    {
        return 'Submissions Overview — ' . $this->getFilterLabel();
    }

    protected function getData(): array
    {
        $parsed = $this->parseWidgetFilter();

        // Date range depends on filter type:
        // - Year filter  → show the full calendar year
        // - Program/All  → show last 90 days
        if ($parsed['type'] === 'year') {
            $year    = $parsed['value'];
            $dateFrom = Carbon::create($year, 1, 1)->startOfDay();
            $dateTo   = $year === now()->year
                ? Carbon::now()
                : Carbon::create($year, 12, 31)->endOfDay();
        } else {
            $dateFrom = Carbon::now()->subDays(90);
            $dateTo   = Carbon::now();
        }

        $base = Submission::select([
            'status',
            DB::raw('DATE(submitted_at) as date'),
            DB::raw('COUNT(*) as count'),
        ])->whereBetween('submitted_at', [$dateFrom, $dateTo]);

        $this->scopeSubmissionsByFilter($base);

        $submissionData = $base->groupBy(['status', 'date'])->orderBy('date')->get()->groupBy('date');

        // Build date labels — weekly buckets for long ranges, daily for short
        $totalDays = (int) $dateFrom->diffInDays($dateTo);
        $useBuckets = $totalDays > 60;

        $dates  = [];
        $labels = [];
        $cursor = $dateFrom->copy();

        while ($cursor <= $dateTo) {
            $dates[]  = $cursor->format('Y-m-d');
            $labels[] = $useBuckets
                ? $cursor->format('M j')   // weekly label
                : $cursor->format('M j');
            $cursor->addDay();
        }

        $pending = $underReview = $completed = $needsRevision = $flagged = [];

        foreach ($dates as $date) {
            $day = $submissionData->get($date, collect());
            $pending[]       = $day->where('status', SubmissionTypes::PENDING_REVIEW->value)->sum('count');
            $underReview[]   = $day->where('status', SubmissionTypes::UNDER_REVIEW->value)->sum('count');
            $completed[]     = $day->where('status', SubmissionTypes::COMPLETED->value)->sum('count');
            $needsRevision[] = $day->where('status', SubmissionTypes::NEEDS_REVISION->value)->sum('count');
            $flagged[]       = $day->where('status', SubmissionTypes::FLAGGED->value)->sum('count');
        }

        return [
            'datasets' => [
                ['label' => 'Pending Review',  'data' => $pending,       'backgroundColor' => 'rgba(59,130,246,0.5)',  'borderColor' => 'rgb(59,130,246)',  'fill' => true],
                ['label' => 'Under Review',    'data' => $underReview,   'backgroundColor' => 'rgba(168,85,247,0.5)', 'borderColor' => 'rgb(168,85,247)', 'fill' => true],
                ['label' => 'Completed',       'data' => $completed,     'backgroundColor' => 'rgba(34,197,94,0.5)',  'borderColor' => 'rgb(34,197,94)',  'fill' => true],
                ['label' => 'Needs Revision',  'data' => $needsRevision, 'backgroundColor' => 'rgba(251,191,36,0.5)', 'borderColor' => 'rgb(251,191,36)', 'fill' => true],
                ['label' => 'Flagged',         'data' => $flagged,       'backgroundColor' => 'rgba(239,68,68,0.5)',  'borderColor' => 'rgb(239,68,68)',  'fill' => true],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string { return 'line'; }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => true, 'position' => 'top']],
            'scales'  => ['y' => ['beginAtZero' => true, 'ticks' => ['stepSize' => 1]]],
        ];
    }
}
