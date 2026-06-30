<?php

namespace App\Filament\Widgets\Concerns;

use App\Models\TrainingProgram;
use Illuminate\Database\Eloquent\Builder;

/**
 * Per-widget year/program filter trait.
 *
 * Filter value format (stored in $this->filter Livewire property):
 *   "year_2026"  → filter by calendar year 2026
 *   "prog_3"     → filter by training program with id=3
 *   ""  / null   → no filter (show all)
 *
 * Each widget declares:
 *   public ?string $filter = null;
 *   protected function getFilters(): ?array { return static::widgetFilterOptions(); }
 */
trait FiltersYearByEnrollment
{
    // ── Filter option builder ─────────────────────────────────────────────────

    /**
     * Build combined year + program options for any widget's getFilters().
     * Always includes a rolling 3-year window so options appear even when
     * the database has no submissions yet.
     */
    public static function widgetFilterOptions(): array
    {
        $options = ['' => '🌐 All Years & Programs'];

        // Year options — last 3 years + current, plus any years with actual data
        $currentYear = now()->year;
        $dbYears = \App\Models\Submission::selectRaw('DISTINCT YEAR(submitted_at) as yr')
            ->orderByDesc('yr')->pluck('yr')->filter()->toArray();

        $years = array_unique(array_merge(
            $dbYears,
            [$currentYear, $currentYear - 1, $currentYear - 2]
        ));
        rsort($years);

        foreach ($years as $year) {
            $options["year_{$year}"] = "📅 {$year}";
        }

        // Training program options — most recent year first
        $programs = TrainingProgram::orderByDesc('year')->orderBy('name')->get();
        if ($programs->isNotEmpty()) {
            foreach ($programs as $prog) {
                $suffix = $prog->year ? " ({$prog->year})" : '';
                $options["prog_{$prog->id}"] = "🎓 {$prog->name}{$suffix}";
            }
        }

        return $options;
    }

    // ── Filter parsing ────────────────────────────────────────────────────────

    /**
     * Parse $this->filter into type + value.
     * Defaults to current year when filter is null/empty.
     */
    protected function parseWidgetFilter(): array
    {
        $raw = $this->filter ?? ('year_' . now()->year);

        if (empty($raw)) {
            return ['type' => 'all', 'value' => null];
        }
        if (str_starts_with($raw, 'year_')) {
            return ['type' => 'year', 'value' => (int) substr($raw, 5)];
        }
        if (str_starts_with($raw, 'prog_')) {
            return ['type' => 'program', 'value' => (int) substr($raw, 5)];
        }
        return ['type' => 'all', 'value' => null];
    }

    /** Convenience for legacy code that reads getSelectedYear(). */
    protected function getSelectedYear(): ?int
    {
        // Legacy dashboard-level filter
        if (isset($this->filters['year']) && $this->filters['year']) {
            return (int) $this->filters['year'];
        }
        $p = $this->parseWidgetFilter();
        return $p['type'] === 'year' ? $p['value'] : null;
    }

    /** Human-readable label for the active filter — use in widget headings. */
    protected function getFilterLabel(): string
    {
        $p = $this->parseWidgetFilter();
        if ($p['type'] === 'year')    return (string) $p['value'];
        if ($p['type'] === 'program') {
            $prog = TrainingProgram::find($p['value']);
            return $prog ? $prog->name . ($prog->year ? " ({$prog->year})" : '') : 'Unknown';
        }
        return 'All';
    }

    // ── Scope helpers ─────────────────────────────────────────────────────────

    /**
     * Scope a User query by the active widget filter.
     *
     * Year mode  → users registered in that year OR still-active prior-year candidates
     * Program mode → users enrolled in that specific training program
     */
    protected function scopeUsersByYear(Builder $query, ?int $year = null): Builder
    {
        return $this->scopeUsersByFilter($query);
    }

    protected function scopeUsersByFilter(Builder $query): Builder
    {
        $p = $this->parseWidgetFilter();

        if ($p['type'] === 'year') {
            $year        = $p['value'];
            $currentYear = now()->year;
            return $query->where(function ($q) use ($year, $currentYear) {
                $q->whereYear('users.created_at', $year);
                // Include active prior-year candidates when viewing current year
                if ($year === $currentYear) {
                    $q->orWhere(fn ($q2) =>
                    $q2->whereYear('users.created_at', '<', $year)
                        ->whereNull('program_completed_at')
                    );
                }
            });
        }

        if ($p['type'] === 'program') {
            return $query->whereHas('enrollments', fn ($q) =>
            $q->where('training_program_id', $p['value'])
            );
        }

        return $query; // 'all' — unscoped
    }

    /**
     * Scope a Submission query by the active widget filter.
     */
    protected function scopeSubmissionsByYear(Builder $query, ?int $year = null): Builder
    {
        return $this->scopeSubmissionsByFilter($query);
    }

    protected function scopeSubmissionsByFilter(Builder $query): Builder
    {
        $p = $this->parseWidgetFilter();

        if ($p['type'] === 'year') {
            $year        = $p['value'];
            $currentYear = now()->year;
            if ($year === $currentYear) {
                return $query->where(function ($q) use ($year) {
                    $q->whereYear('submissions.submitted_at', $year)
                        ->orWhereHas('student', fn ($sq) =>
                        $sq->whereYear('users.created_at', '<', $year)
                            ->whereNull('program_completed_at')
                        );
                });
            }
            return $query->whereYear('submissions.submitted_at', $year);
        }

        if ($p['type'] === 'program') {
            return $query->whereHas('task.section', fn ($q) =>
            $q->where('training_program_id', $p['value'])
            );
        }

        return $query;
    }

    /**
     * Scope a ProgramEnrollment query by the active widget filter.
     */
    protected function scopeEnrollmentsByYear(Builder $query, ?int $year = null): Builder
    {
        return $this->scopeEnrollmentsByFilter($query);
    }

    protected function scopeEnrollmentsByFilter(Builder $query): Builder
    {
        $p = $this->parseWidgetFilter();
        if ($p['type'] === 'year')    return $query->whereHas('trainingProgram', fn ($q) => $q->where('year', $p['value']));
        if ($p['type'] === 'program') return $query->where('training_program_id', $p['value']);
        return $query;
    }

    /**
     * Get scoped user IDs for the current filter.
     * Useful when you need to scope withCount() on a related model.
     */
    protected function scopedUserIds(): array
    {
        return $this->scopeUsersByFilter(\App\Models\User::query())->pluck('id')->toArray();
    }
}
