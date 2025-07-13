<?php

namespace App\Filament\Widgets;

use App\Models\District;
use App\Models\Church;
use App\Models\User;
use Filament\Widgets\ChartWidget;

class StudentDistributionBarChart extends ChartWidget
{
    protected static ?string $heading = 'Student Distribution (Bar Chart)';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $maxHeight = '400px';
    protected static bool $isLazy = true;

    public ?string $filter = 'church';

    protected function getFilters(): ?array
    {
        return [
            'church' => 'By Church',
            'district' => 'By District',
        ];
    }

    protected function getData(): array
    {
        $filter = $this->filter;

        if ($filter === 'district') {
            return $this->getDistrictBarData();
        }

        return $this->getChurchBarData();
    }

    private function getChurchBarData(): array
    {
        $churchData = Church::withCount(['users' => function ($query) {
            // Add any conditions here to filter only students
        }])
            ->where('is_active', true)
            ->orderBy('users_count', 'desc')
            ->take(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Number of Students',
                    'data' => $churchData->pluck('users_count')->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $churchData->pluck('name')->toArray(),
        ];
    }

    private function getDistrictBarData(): array
    {
        $districtData = District::withCount(['churches' => function ($query) {
            $query->where('is_active', true);
        }])
            ->with(['churches' => function ($query) {
                $query->where('is_active', true)->withCount('users');
            }])
            ->get()
            ->map(function ($district) {
                $totalStudents = $district->churches->sum('users_count');
                return [
                    'name' => $district->name,
                    'students_count' => $totalStudents,
                ];
            })
            ->sortByDesc('students_count')
            ->take(10)
            ->values();

        return [
            'datasets' => [
                [
                    'label' => 'Number of Students',
                    'data' => $districtData->pluck('students_count')->toArray(),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.8)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $districtData->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
                'x' => [
                    'ticks' => [
                        'maxRotation' => 45,
                        'minRotation' => 0,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            return context.dataset.label + ": " + context.parsed.y + " students";
                        }',
                    ],
                ],
                'datalabels' => [
                    'display' => true,
                    'align' => 'top',
                    'anchor' => 'end',
                    'color' => 'rgb(75, 85, 99)',
                    'font' => [
                        'weight' => 'bold',
                        'size' => 12,
                    ],
                    'formatter' => 'function(value) {
                        return value;
                    }',
                ],
            ],
        ];
    }
//
    public function getHeading(): string
    {
        return $this->filter === 'district'
            ? 'Student Distribution by District (Bar Chart)'
            : 'Student Distribution by Church (Bar Chart)';
    }
}

