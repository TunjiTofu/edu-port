<?php

namespace App\Filament\Widgets;

use App\Models\District;
use App\Models\Church;
use App\Models\User;
use Filament\Widgets\ChartWidget;

class StudentDistributionChart extends ChartWidget
{
    protected static ?string $heading = 'Student Distribution by Church';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $maxHeight = '400px';
    protected static bool $isLazy = true; // Lazy loading

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
            return $this->getDistrictData();
        }

        return $this->getChurchData();
    }

    private function getChurchData(): array
    {
        // Get student count by church (assuming students are users with specific role or criteria)
        // Adjust the query based on how you identify students in your system
        $churchData = Church::withCount(['users' => function ($query) {
            // Add any conditions here to filter only students
            // For example: $query->where('role', 'student');
            // Or: $query->whereHas('roles', function($q) { $q->where('name', 'student'); });
        }])
            ->where('is_active', true)
            ->orderBy('users_count', 'desc')
            ->take(10) // Limit to top 10 churches
            ->get();

        // Create labels with church names and student counts
        $labels = $churchData->map(function ($church) {
            return $church->name . ' (' . $church->users_count . ')';
        })->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Students',
                    'data' => $churchData->pluck('users_count')->toArray(),
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
            'labels' => $labels,
        ];
    }

    private function getDistrictData(): array
    {
        // Get student count by district
        $districtData = District::withCount(['churches' => function ($query) {
            $query->where('is_active', true);
        }])
            ->with(['churches' => function ($query) {
                $query->where('is_active', true)->withCount('users');
            }])
            ->orderBy('name')
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

        // Create labels with district names and student counts
        $labels = $districtData->map(function ($district) {
            return $district['name'] . ' (' . $district['students_count'] . ')';
        })->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Students',
                    'data' => $districtData->pluck('students_count')->toArray(),
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
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
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
                        'padding' => 15,
                        'font' => [
                            'size' => 12,
                        ],
                    ],
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            var label = context.label || "";
                            var value = context.parsed || 0;
                            var total = context.dataset.data.reduce((a, b) => a + b, 0);
                            var percentage = Math.round((value / total) * 100);
                            return label + ": " + value + " students (" + percentage + "%)";
                        }',
                    ],
                ],
                'datalabels' => [
                    'display' => true,
                    'color' => 'white',
                    'font' => [
                        'weight' => 'bold',
                        'size' => 14,
                    ],
                    'formatter' => 'function(value, context) {
                        var total = context.dataset.data.reduce((a, b) => a + b, 0);
                        var percentage = Math.round((value / total) * 100);
                        return percentage > 5 ? value : "";
                    }',
                ],
            ],
            'cutout' => '50%',
        ];
    }

    public function getHeading(): string
    {
        return $this->filter === 'district'
            ? 'Student Distribution by District'
            : 'Student Distribution by Church';
    }
}
