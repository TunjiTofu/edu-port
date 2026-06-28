<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersYearByEnrollment;
use App\Models\Church;
use App\Models\District;
use Filament\Widgets\ChartWidget;

class StudentDistributionChart extends ChartWidget
{
    use FiltersYearByEnrollment;

    protected static ?int    $sort            = 2;
    protected int|string|array $columnSpan    = 'full';
    protected static ?string $maxHeight       = '400px';
    protected static bool    $isLazy          = true;

    // "group_church" / "group_district" are grouping toggles.
    // Everything else is a year/program scope from the trait.
    public ?string $filter = null;

    /**
     * Separate property for grouping — so we can have a year filter
     * AND a church/district toggle independently.
     * Default to district grouping.
     */
    public string $groupBy = 'district';

    protected function getFilters(): ?array
    {
        // Merge grouping options + year/program options
        return [
                'group_church'   => '⛪ Group by Church',
                'group_district' => '🏘 Group by District',
            ] + static::widgetFilterOptions();
    }

    public function getHeading(): string
    {
        $scope = $this->getFilterLabel();
        $group = $this->groupBy === 'church' ? 'Church' : 'District';
        return "Student Distribution by {$group} — {$scope}";
    }

    protected function getData(): array
    {
        // Handle grouping toggle options separately
        if ($this->filter === 'group_church') {
            $this->groupBy = 'church';
            return $this->getChurchData();
        }
        if ($this->filter === 'group_district') {
            $this->groupBy = 'district';
            return $this->getDistrictData();
        }

        // Year/program filter — keep current groupBy, scope the data
        return $this->groupBy === 'church'
            ? $this->getChurchData()
            : $this->getDistrictData();
    }

    private function getChurchData(): array
    {
        // Get scoped user IDs so withCount respects the year/program filter
        $scopedIds = $this->getIsGroupingFilter()
            ? null
            : $this->scopedUserIds();

        $churchData = Church::withCount(['users' => function ($q) use ($scopedIds) {
            if ($scopedIds !== null) {
                $q->whereIn('users.id', $scopedIds);
            }
        }])
            ->where('is_active', true)
            ->orderByDesc('users_count')
            ->take(10)
            ->get();

        return [
            'datasets' => [[
                'label'           => 'Students',
                'data'            => $churchData->pluck('users_count')->toArray(),
                'backgroundColor' => $this->palette(),
                'borderColor'     => 'rgb(255,255,255)',
                'borderWidth'     => 2,
            ]],
            'labels' => $churchData->map(fn ($c) => $c->name . ' (' . $c->users_count . ')')->toArray(),
        ];
    }

    private function getDistrictData(): array
    {
        $scopedIds = $this->getIsGroupingFilter()
            ? null
            : $this->scopedUserIds();

        $districtData = District::with(['churches' => function ($q) use ($scopedIds) {
            $q->where('is_active', true)->withCount(['users' => function ($q2) use ($scopedIds) {
                if ($scopedIds !== null) {
                    $q2->whereIn('users.id', $scopedIds);
                }
            }]);
        }])
            ->get()
            ->map(fn ($d) => [
                'name'   => $d->name,
                'count'  => $d->churches->sum('users_count'),
            ])
            ->sortByDesc('count')
            ->take(10)
            ->values();

        return [
            'datasets' => [[
                'label'           => 'Students',
                'data'            => $districtData->pluck('count')->toArray(),
                'backgroundColor' => $this->palette(),
                'borderColor'     => 'rgb(255,255,255)',
                'borderWidth'     => 2,
            ]],
            'labels' => $districtData->map(fn ($d) => $d['name'] . ' (' . $d['count'] . ')')->toArray(),
        ];
    }

    /** True when the current filter is a grouping toggle, not a year/program scope. */
    private function getIsGroupingFilter(): bool
    {
        return in_array($this->filter, ['group_church', 'group_district', '', null]);
    }

    private function palette(): array
    {
        return [
            'rgb(59,130,246)', 'rgb(16,185,129)', 'rgb(245,158,11)', 'rgb(239,68,68)',
            'rgb(139,92,246)', 'rgb(236,72,153)', 'rgb(20,184,166)', 'rgb(251,146,60)',
            'rgb(156,163,175)', 'rgb(34,197,94)',
        ];
    }

    protected function getType(): string { return 'doughnut'; }

    protected function getOptions(): array
    {
        return [
            'responsive'          => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend'  => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'padding' => 15]],
                'tooltip' => ['callbacks' => ['label' => 'function(c){var t=c.dataset.data.reduce((a,b)=>a+b,0);var p=Math.round(c.parsed/t*100);return c.label+": "+c.parsed+" ("+p+"%)"}']],
            ],
            'cutout' => '50%',
        ];
    }
}
