<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class AdminDashboard extends Dashboard
{
    use HasFiltersForm;

    protected static ?string $navigationIcon  = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?int    $navigationSort  = -1;

    /**
     * The year filter is rendered at the top of the dashboard.
     * All widgets on this page receive $this->filters['year'] automatically.
     *
     * "Current year" (default) shows:
     *   - Candidates who enrolled/registered in the current year
     *   - Candidates from previous years who are still ACTIVE (not graduated)
     *
     * This gives the admin a meaningful "who is active right now" view
     * without hiding continuing candidates from previous cohorts.
     */
    public function filtersForm(Form $form): Form
    {
        $currentYear = now()->year;

        // Build year options dynamically from actual data
        $years = User::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year', 'year')
            ->map(fn ($y) => (string) $y)
            ->toArray();

        // Always include current year even if no users yet
        $years[$currentYear] = (string) $currentYear;
        krsort($years);

        return $form
            ->schema([
                Select::make('year')
                    ->label('Viewing year')
                    ->options($years)
                    ->default((string) $currentYear)
                    ->native(false)
                    ->placeholder('All Years')
                    ->extraAttributes(['class' => 'min-w-[140px]']),
            ]);
    }
}
