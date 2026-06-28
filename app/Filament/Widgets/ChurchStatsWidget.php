<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersYearByEnrollment;
use App\Models\Church;
use App\Models\District;
use App\Models\Section;
use App\Models\Task;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ChurchStatsWidget extends BaseWidget
{
    use FiltersYearByEnrollment;

    // ── Custom view renders the filter select + stats grid ─────────────────
    // StatsOverviewWidget does not render getFilters() natively, so we use
    // a custom Blade view that adds wire:model.live="filter" select manually.
    protected static string $view = 'filament.widgets.stats-widget-with-filter';

    protected static ?int $sort            = 1;
    protected int|string|array $columnSpan = 'full';
    protected static bool $isLazy          = true;

    public ?string $filter = null;

    // ── Public wrappers called from the blade view ─────────────────────────
    public function getWidgetHeading(): string     { return $this->getHeading(); }
    public function getWidgetDescription(): ?string { return $this->getDescription(); }
    public function getWidgetFilters(): ?array     { return $this->getFilters(); }
    public function getWidgetStats(): array        { return $this->getStats(); }

    protected function getFilters(): ?array
    {
        return static::widgetFilterOptions();
    }

    protected function getColumns(): int { return 4; }

    protected function getHeading(): string
    {
        return 'Church Overview — ' . $this->getFilterLabel();
    }

    protected function getDescription(): ?string
    {
        return 'Church and programme statistics for the selected filter.';
    }

    protected function getStats(): array
    {
        $totalChurches   = Church::count();
        $activeChurches  = Church::where('is_active', true)->count();
        $inactiveChurches = $totalChurches - $activeChurches;
        $totalDistricts  = District::count();

        $totalMembers        = Church::withCount('users')->get()->sum('users_count');
        $avgMembersPerChurch = $totalChurches > 0 ? round($totalMembers / $totalChurches, 1) : 0;

        $recentChurches  = Church::where('created_at', '>=', now()->subDays(30))->count();
        $previousChurches = Church::whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])->count();
        $growthRate      = $previousChurches > 0
            ? round((($recentChurches - $previousChurches) / $previousChurches) * 100, 1)
            : ($recentChurches > 0 ? 100 : 0);

        $topChurch = Church::withCount('users')->orderByDesc('users_count')->first();

        $totalTasks   = Task::count();
        $activeTasks  = Task::where('is_active', true)->count();
        $overdueTasks = Task::where('is_active', true)->where('due_date', '<', now())->count();

        $totalSections  = Section::count();
        $activeSections = Section::where('is_active', true)->count();

        return [
            Stat::make('Total Churches', $totalChurches)
                ->description($inactiveChurches . ' inactive')
                ->descriptionIcon($inactiveChurches > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($inactiveChurches > 0 ? 'warning' : 'primary')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

            Stat::make('Active Churches', $activeChurches)
                ->description($inactiveChurches . ' inactive')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('success')
                ->chart([4, 6, 8, 5, 7, 9, 6, 8]),

            Stat::make('Total Members', number_format($totalMembers))
                ->description("Avg: {$avgMembersPerChurch} per church")
                ->descriptionIcon('heroicon-m-users')
                ->color('info')
                ->chart([12, 15, 18, 16, 20, 22, 19, 24]),

            Stat::make('Districts', $totalDistricts)
                ->description('Active districts')
                ->descriptionIcon('heroicon-m-map')
                ->color('gray')
                ->chart([2, 3, 2, 4, 3, 2, 3, 4]),

            Stat::make('New This Month', $recentChurches)
                ->description($growthRate >= 0 ? "+{$growthRate}% growth" : "{$growthRate}% decline")
                ->descriptionIcon($growthRate >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($growthRate >= 0 ? 'success' : 'danger'),

            Stat::make('Top Church', $topChurch?->name ?? 'N/A')
                ->description($topChurch ? "{$topChurch->users_count} members" : 'No data')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('warning'),

            Stat::make('Total Tasks', $totalTasks)
                ->description("{$activeTasks} active" . ($overdueTasks > 0 ? ", {$overdueTasks} overdue" : ''))
                ->descriptionIcon($overdueTasks > 0 ? 'heroicon-m-clock' : 'heroicon-m-clipboard-document-list')
                ->color($overdueTasks > 0 ? 'warning' : 'success'),

            Stat::make('Total Sections', $totalSections)
                ->description("{$activeSections} active")
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('info'),
        ];
    }
}
