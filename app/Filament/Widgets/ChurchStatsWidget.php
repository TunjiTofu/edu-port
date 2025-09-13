<?php

namespace App\Filament\Widgets;

use App\Models\Church;
use App\Models\District;
use App\Models\Submission;
use App\Models\Task;
use App\Models\Section;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ChurchStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '30s';
    protected int | string | array $columnSpan = 'full';
    protected static bool $isLazy = true; // Lazy loading

    protected function getColumns(): int
    {
        return 4; // This sets 4 stat cards per row
    }

    protected function getHeading(): string
    {
        return "Church Overview";
    }
    protected function getDescription(): ?string
    {
        return 'Real-time church statistics and metrics';
    }

    protected function getStats(): array
    {
        // Get basic counts
        $totalChurches = Church::count();
        $activeChurches = Church::where('is_active', true)->count();
        $inactiveChurches = Church::where('is_active', false)->count();
        $totalDistricts = District::count();

        // Get member statistics
        $totalMembers = Church::withCount('users')->get()->sum('users_count');
        $avgMembersPerChurch = $totalChurches > 0 ? round($totalMembers / $totalChurches, 1) : 0;

        // Get recent activity (churches created in the last 30 days)
        $recentChurches = Church::where('created_at', '>=', now()->subDays(30))->count();

        // Get church with most members
        $topChurch = Church::withCount('users')
            ->orderBy('users_count', 'desc')
            ->first();

        // Calculate growth percentage (compare last 30 days vs previous 30 days)
        $currentPeriod = Church::where('created_at', '>=', now()->subDays(30))->count();
        $previousPeriod = Church::whereBetween('created_at', [
            now()->subDays(60),
            now()->subDays(30)
        ])->count();

        $growthRate = $previousPeriod > 0
            ? round((($currentPeriod - $previousPeriod) / $previousPeriod) * 100, 1)
            : ($currentPeriod > 0 ? 100 : 0);

        // Get task statistics
        $totalTasks = Task::count();
        $activeTasks = Task::where('is_active', true)->count();
        $inactiveTasks = Task::where('is_active', false)->count();

        // Get overdue tasks (where due_date is past and task is active)
        $overdueTasks = Task::where('is_active', true)
            ->where('due_date', '<', now())
            ->count();

        // Get section statistics
        $totalSections = Section::count();
        $activeSections = Section::where('is_active', true)->count();
        $inactiveSections = Section::where('is_active', false)->count();

        // Get average tasks per section
        $avgTasksPerSection = $totalSections > 0 ? round($totalTasks / $totalSections, 1) : 0;

        return [
            Stat::make('Total Churches', $totalChurches)
                ->description('Churches in system')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            Stat::make('Active Churches', $activeChurches)
                ->description($inactiveChurches . ' inactive')
                ->descriptionIcon($inactiveChurches > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($inactiveChurches > 0 ? 'warning' : 'success')
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
                ->color($growthRate >= 0 ? 'success' : 'danger')
                ->chart($growthRate >= 0 ? [1, 2, 1, 3, 2, 4, 3, 5] : [5, 3, 4, 2, 3, 1, 2, 1]),

            Stat::make('Top Church', $topChurch?->name ?? 'N/A')
                ->description($topChurch ? "{$topChurch->users_count} members" : 'No data')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('warning')
                ->chart([8, 12, 15, 18, 20, 22, 25, 28]),

            Stat::make('Total Tasks', $totalTasks)
                ->description("{$activeTasks} active, {$inactiveTasks} inactive")
                ->descriptionIcon($overdueTasks > 0 ? 'heroicon-m-clock' : 'heroicon-m-clipboard-document-list')
                ->color($overdueTasks > 0 ? 'warning' : 'success')
                ->chart([5, 8, 12, 15, 18, 20, 22, 25])
                ->extraAttributes([
                    'title' => $overdueTasks > 0 ? "{$overdueTasks} overdue tasks" : 'All tasks up to date',
                ]),

            Stat::make('Total Sections', $totalSections)
                ->description("{$activeSections} active, {$inactiveSections} inactive")
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('info')
                ->chart([3, 5, 7, 8, 10, 12, 14, 16]),

        ];
    }
}
