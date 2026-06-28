<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersYearByEnrollment;
use App\Models\Church;
use App\Models\District;
use Filament\Widgets\ChartWidget;

class StudentDistributionBarChart extends ChartWidget
{
    use FiltersYearByEnrollment;

    protected static ?int    $sort            = 3;
    protected int|string|array $columnSpan    = 'full';
    protected static ?string $maxHeight       = '400px';
    protected static bool    $isLazy          = true;

    public ?string $filter = null;
    public string  $groupBy = 'district';

    protected function getFilters(): ?array
    {
        return [
                'group_church'   => '⛪ Group by Church',
                'group_district' => '🏘 Group by District',
            ] + static::widgetFilterOptions();
    }

    public function getHeading(): string
    {
        $scope = $this->getFilterLabel();
        $group = $this->groupBy === 'church' ? 'Church' : 'District';
        return "Student Distribution by {$group} (Bar) — {$scope}";
    }

    protected function getData(): array
    {
        if ($this->filter === 'group_church') {
            $this->groupBy = 'church';
            return $this->getChurchBarData();
        }
        if ($this->filter === 'group_district') {
            $this->groupBy = 'district';
            return $this->getDistrictBarData();
        }
        return $this->groupBy === 'church'
            ? $this->getChurchBarData()
            : $this->getDistrictBarData();
    }

    private function isGroupingFilter(): bool
    {
        return in_array($this->filter, ['group_church', 'group_district', '', null]);
    }

    private function getChurchBarData(): array
    {
        $scopedIds = $this->isGroupingFilter() ? null : $this->scopedUserIds();

        $churchData = Church::withCount(['users' => function ($q) use ($scopedIds) {
            if ($scopedIds !== null) $q->whereIn('users.id', $scopedIds);
        }])
            ->where('is_active', true)
            ->orderByDesc('users_count')
            ->take(10)->get();

        return [
            'datasets' => [[
                'label'           => 'Number of Students',
                'data'            => $churchData->pluck('users_count')->toArray(),
                'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                'borderColor'     => 'rgb(59, 130, 246)',
                'borderWidth'     => 1,
            ]],
            'labels' => $churchData->pluck('name')->toArray(),
        ];
    }

    private function getDistrictBarData(): array
    {
        $scopedIds = $this->isGroupingFilter() ? null : $this->scopedUserIds();

        $districtData = District::with(['churches' => function ($q) use ($scopedIds) {
            $q->where('is_active', true)->withCount(['users' => function ($q2) use ($scopedIds) {
                if ($scopedIds !== null) $q2->whereIn('users.id', $scopedIds);
            }]);
        }])
            ->get()
            ->map(fn ($d) => ['name' => $d->name, 'count' => $d->churches->sum('users_count')])
            ->sortByDesc('count')->take(10)->values();

        return [
            'datasets' => [[
                'label'           => 'Number of Students',
                'data'            => $districtData->pluck('count')->toArray(),
                'backgroundColor' => 'rgba(16, 185, 129, 0.8)',
                'borderColor'     => 'rgb(16, 185, 129)',
                'borderWidth'     => 1,
            ]],
            'labels' => $districtData->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string { return 'bar'; }

    protected function getOptions(): array
    {
        return [
            'responsive'          => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['stepSize' => 1]],
                'x' => ['ticks' => ['maxRotation' => 45]],
            ],
            'plugins' => ['legend' => ['display' => true, 'position' => 'top']],
        ];
    }
}
