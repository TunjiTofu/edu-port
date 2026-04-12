<?php

namespace App\Filament\Student\Widgets;

use App\Models\Submission;
use Filament\Widgets\ChartWidget;

class PerformanceChartWidget extends ChartWidget
{
    protected static ?string $heading     = 'Performance Trend';
    protected static ?string $description = 'Your score progression over time (as % of maximum)';
    protected static ?int    $sort        = 4;
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $submissions = Submission::where('student_id', auth()->id())
            ->whereHas('review', fn ($q) => $q->whereNotNull('score'))
            ->whereHas('task.resultPublication', fn ($q) => $q->where('is_published', true))
            ->with(['review:id,submission_id,score,reviewed_at', 'task:id,title,max_score'])
            ->get()
            ->sortBy('review.reviewed_at');

        if ($submissions->isEmpty()) {
            return [
                'datasets' => [[
                    'label'           => 'No results published yet',
                    'data'            => [],
                    'borderColor'     => '#9CA3AF',
                    'backgroundColor' => 'rgba(156, 163, 175, 0.1)',
                ]],
                'labels' => [],
            ];
        }

        $labels = $submissions->map(fn ($s) => $s->task?->title ?? 'Unknown')->values()->toArray();

        $scorePercentages = $submissions->map(function ($s) {
            $score    = (float) ($s->review?->score ?? 0);
            $maxScore = (float) ($s->task?->max_score ?? 10);

            if ($maxScore <= 0) return 0;

            return round(($score / $maxScore) * 100, 1);
        })->values()->toArray();

        $averagePct = count($scorePercentages) > 0
            ? round(array_sum($scorePercentages) / count($scorePercentages), 1)
            : 0;

        return [
            'datasets' => [
                [
                    'label'           => 'Your Score (%)',
                    'data'            => $scorePercentages,
                    'borderColor'     => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill'            => true,
                    'tension'         => 0.4,
                ],
                [
                    'label'           => 'Your Average (' . $averagePct . '%)',
                    'data'            => array_fill(0, count($scorePercentages), $averagePct),
                    'borderColor'     => '#EF4444',
                    'backgroundColor' => 'transparent',
                    'borderDash'      => [5, 5],
                    'pointRadius'     => 0,
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
                'legend' => ['display' => true],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(ctx) { return ctx.dataset.label + ": " + ctx.parsed.y + "%"; }',
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'max'         => 100,
                    'ticks'       => [
                        // FIX: The JS callback string is now correct — scores are
                        // now actual percentages so the '%' suffix is accurate.
                        'callback' => 'function(value) { return value + "%"; }',
                    ],
                    'title' => [
                        'display' => true,
                        'text'    => 'Score (%)',
                    ],
                ],
            ],
        ];
    }
}
