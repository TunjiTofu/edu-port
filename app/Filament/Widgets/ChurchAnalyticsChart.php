<?php

namespace App\Filament\Widgets;

use App\Models\District;
use Filament\Widgets\ChartWidget;

class ChurchAnalyticsChart extends ChartWidget
{
    protected static ?string $heading = 'Church Distribution by District';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $maxHeight = '200px';
    protected static bool $isLazy = true; // Lazy loading

    protected function getColumns(): int
    {
        return 4; // This sets 4 stat cards per row
    }

    protected function getData(): array
    {
        // Get church count by district
        $districtData = District::withCount(['churches' => function ($query) {
            $query->where('is_active', true);
        }])
            ->orderBy('churches_count', 'desc')
            ->take(10) // Limit to top 10 districts
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Active Churches',
                    'data' => $districtData->pluck('churches_count')->toArray(),
                    'backgroundColor' => [
                        'rgb(59, 130, 246)',   // Blue
                        'rgb(16, 185, 129)',   // Green
                        'rgb(245, 158, 11)',   // Yellow
                        'rgb(239, 68, 68)',    // Red
                        'rgb(139, 92, 246)',   // Purple
                        'rgb(236, 72, 153)',   // Pink
                        'rgb(20, 184, 166)',   // Teal
                        'rgb(251, 146, 60)',   // Orange
                        'rgb(156, 163, 175)',  // Gray
                        'rgb(34, 197, 94)',    // Emerald
                    ],
                    'borderColor' => 'rgb(255, 255, 255)',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $districtData->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
//        return 'pie';
//        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20,
                    ],
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return context.label + ": " + context.parsed + " churches"; }',
                    ],
                ],
            ],
            'cutout' => '60%',
        ];
    }
}

// Alternative Line Chart Widget for Church Growth Over Time
//class ChurchGrowthChart extends ChartWidget
//{
//    protected static ?string $heading = 'Church Registration Trend (Last 12 Months)';
//    protected static ?int $sort = 3;
//    protected int | string | array $columnSpan = 'full';
//    protected static ?string $maxHeight = '300px';
//
//    protected function getData(): array
//    {
//        // Get church registrations by month for the last 12 months
//        $monthlyData = Church::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
//            ->where('created_at', '>=', now()->subMonths(12))
//            ->groupBy('month')
//            ->orderBy('month')
//            ->get()
//            ->keyBy('month');
//
//        // Generate last 12 months
//        $months = collect();
//        for ($i = 11; $i >= 0; $i--) {
//            $month = now()->subMonths($i)->format('Y-m');
//            $months->push([
//                'month' => $month,
//                'label' => now()->subMonths($i)->format('M Y'),
//                'count' => $monthlyData->get($month)?->count ?? 0,
//            ]);
//        }
//
//        return [
//            'datasets' => [
//                [
//                    'label' => 'New Churches',
//                    'data' => $months->pluck('count')->toArray(),
//                    'borderColor' => 'rgb(59, 130, 246)',
//                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
//                    'fill' => true,
//                    'tension' => 0.4,
//                    'pointBackgroundColor' => 'rgb(59, 130, 246)',
//                    'pointBorderColor' => 'rgb(255, 255, 255)',
//                    'pointBorderWidth' => 2,
//                    'pointRadius' => 4,
//                ],
//            ],
//            'labels' => $months->pluck('label')->toArray(),
//        ];
//    }
//
//    protected function getType(): string
//    {
//        return 'line';
//    }
//
//    protected function getOptions(): array
//    {
//        return [
//            'responsive' => true,
//            'maintainAspectRatio' => false,
//            'scales' => [
//                'y' => [
//                    'beginAtZero' => true,
//                    'ticks' => [
//                        'stepSize' => 1,
//                    ],
//                ],
//            ],
//            'plugins' => [
//                'legend' => [
//                    'display' => false,
//                ],
//                'tooltip' => [
//                    'mode' => 'index',
//                    'intersect' => false,
//                ],
//            ],
//            'interaction' => [
//                'mode' => 'nearest',
//                'axis' => 'x',
//                'intersect' => false,
//            ],
//        ];
//    }
//}

// Alternative Line Chart Widget for Church Growth Over Time
//class ChurchGrowthChart extends ChartWidget
//{
//    protected static ?string $heading = 'Church Registration Trend (Last 12 Months)';
//    protected static ?int $sort = 3;
//    protected int | string | array $columnSpan = 'full';
//    protected static ?string $maxHeight = '300px';
//
//    protected function getData(): array
//    {
//        // Get church registrations by month for the last 12 months
//        $monthlyData = Church::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
//            ->where('created_at', '>=', now()->subMonths(12))
//            ->groupBy('month')
//            ->orderBy('month')
//            ->get()
//            ->keyBy('month');
//
//        // Generate last 12 months
//        $months = collect();
//        for ($i = 11; $i >= 0; $i--) {
//            $month = now()->subMonths($i)->format('Y-m');
//            $months->push([
//                'month' => $month,
//                'label' => now()->subMonths($i)->format('M Y'),
//                'count' => $monthlyData->get($month)?->count ?? 0,
//            ]);
//        }
//
//        return [
//            'datasets' => [
//                [
//                    'label' => 'New Churches',
//                    'data' => $months->pluck('count')->toArray(),
//                    'borderColor' => 'rgb(59, 130, 246)',
//                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
//                    'fill' => true,
//                    'tension' => 0.4,
//                    'pointBackgroundColor' => 'rgb(59, 130, 246)',
//                    'pointBorderColor' => 'rgb(255, 255, 255)',
//                    'pointBorderWidth' => 2,
//                    'pointRadius' => 4,
//                ],
//            ],
//            'labels' => $months->pluck('label')->toArray(),
//        ];
//    }
//
//    protected function getType(): string
//    {
//        return 'line';
//    }
//
//    protected function getOptions(): array
//    {
//        return [
//            'responsive' => true,
//            'maintainAspectRatio' => false,
//            'scales' => [
//                'y' => [
//                    'beginAtZero' => true,
//                    'ticks' => [
//                        'stepSize' => 1,
//                    ],
//                ],
//            ],
//            'plugins' => [
//                'legend' => [
//                    'display' => false,
//                ],
//                'tooltip' => [
//                    'mode' => 'index',
//                    'intersect' => false,
//                ],
//            ],
//            'interaction' => [
//                'mode' => 'nearest',
//                'axis' => 'x',
//                'intersect' => false,
//            ],
//        ];
//    }
//}
