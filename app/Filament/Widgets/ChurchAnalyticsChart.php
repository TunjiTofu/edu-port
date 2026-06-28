<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersYearByEnrollment;
use App\Models\District;
use Filament\Widgets\ChartWidget;

class ChurchAnalyticsChart extends ChartWidget
{
    use FiltersYearByEnrollment;

    protected static ?int    $sort            = 2;
    protected int|string|array $columnSpan    = 'full';
    protected static ?string $maxHeight       = '200px';
    protected static bool    $isLazy          = true;

    public ?string $filter = null;

    protected function getFilters(): ?array
    {
        return static::widgetFilterOptions();
    }

    public function getHeading(): string
    {
        return 'Church Distribution by District — ' . $this->getFilterLabel();
    }

    protected function getData(): array
    {
        $districtData = District::withCount(['churches' => fn ($q) => $q->where('is_active', true)])
            ->orderByDesc('churches_count')
            ->take(10)
            ->get();

        return [
            'datasets' => [[
                'label'           => 'Active Churches',
                'data'            => $districtData->pluck('churches_count')->toArray(),
                'backgroundColor' => [
                    'rgb(59, 130, 246)', 'rgb(16, 185, 129)', 'rgb(245, 158, 11)', 'rgb(239, 68, 68)',
                    'rgb(139, 92, 246)', 'rgb(236, 72, 153)', 'rgb(20, 184, 166)', 'rgb(251, 146, 60)',
                    'rgb(156, 163, 175)', 'rgb(34, 197, 94)',
                ],
                'borderColor' => 'rgb(255,255,255)',
                'borderWidth' => 2,
            ]],
            'labels' => $districtData->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string { return 'doughnut'; }

    protected function getOptions(): array
    {
        return [
            'responsive'          => true,
            'maintainAspectRatio' => false,
            'plugins'             => [
                'legend'  => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'padding' => 20]],
                'tooltip' => ['callbacks' => ['label' => 'function(c) { return c.label+": "+c.parsed+" churches"; }']],
            ],
            'cutout' => '60%',
        ];
    }
}
