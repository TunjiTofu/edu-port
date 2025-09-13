<?php

namespace App\Filament\Student\Widgets;

use Filament\Widgets\ChartWidget; // THIS is the correct import
use App\Models\Submission;
use Carbon\Carbon;

class PerformanceChartWidget extends ChartWidget // Extend ChartWidget, not BaseWidget
{
    protected static ?string $heading = 'Performance Trend'; // Uncomment these
    protected static ?string $description = 'Your score progression over time';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $submissions = Submission::where('student_id', auth()->id())
            ->whereHas('review', function ($query) {
                $query->whereNotNull('score');
            })
            ->whereHas('task', function ($query) {
                $query->whereHas('resultPublication', function ($subQuery) {
                    $subQuery->where('is_published', true);
                });
            })
            ->with(['review', 'task'])
            ->orderBy('created_at') // Change this - can't order by relationship field directly
            ->get()
            ->sortBy('review.created_at'); // Sort after loading if needed

        if ($submissions->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'label' => 'No data available',
                        'data' => [],
                        'borderColor' => '#9CA3AF',
                        'backgroundColor' => 'rgba(156, 163, 175, 0.1)',
                    ],
                ],
                'labels' => [],
            ];
        }

        $labels = $submissions->map(function ($submission) {
            return $submission->task->title;
        })->toArray();

        $scores = $submissions->map(function ($submission) {
            return $submission->review->score;
        })->toArray();

        $averageScore = collect($scores)->avg();

        return [
            'datasets' => [
                [
                    'label' => 'Your Scores',
                    'data' => $scores,
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Average Performance',
                    'data' => array_fill(0, count($scores), round($averageScore, 1)),
                    'borderColor' => '#EF4444',
                    'backgroundColor' => 'transparent',
                    'borderDash' => [5, 5],
                    'pointRadius' => 0,
                ],
            ],
            'labels' => $labels,
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
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'max' => 100,
                    'ticks' => [
                        'callback' => 'function(value) { return value + "%"; }',
                    ],
                ],
            ],
        ];
    }
}
