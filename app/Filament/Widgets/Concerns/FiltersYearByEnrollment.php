<?php

namespace App\Filament\Widgets\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Add this trait to every admin dashboard widget that needs year filtering.
 *
 * Usage in a widget method:
 *
 *   use Concerns\FiltersYearByEnrollment;
 *
 *   protected function getTableQuery(): Builder
 *   {
 *       return parent::getTableQuery()
 *           ->when(true, fn ($q) => $this->applyYearScope($q));
 *   }
 *
 *   // Or in a stats widget:
 *   protected function getStats(): array
 *   {
 *       $year = $this->getSelectedYear();
 *       $query = Submission::query();
 *       $this->scopeByYear($query, $year);
 *       ...
 *   }
 */
trait FiltersYearByEnrollment
{
    /**
     * Get the selected year from the dashboard filter form.
     * Falls back to the current year if no filter is set.
     */
    protected function getSelectedYear(): ?int
    {
        $year = $this->filters['year'] ?? null;
        return $year ? (int) $year : now()->year;
    }

    /**
     * Apply year scope to a User/Candidate query.
     *
     * "Active this year" includes:
     *   1. Users who registered in the selected year (new cohort)
     *   2. Users who registered in a PREVIOUS year but are still active
     *      (program_completed_at IS NULL) — continuing candidates
     *
     * This ensures the default "current year" view shows all working candidates,
     * not just those who enrolled this year.
     */
    protected function scopeUsersByYear(Builder $query, ?int $year = null): Builder
    {
        $year ??= $this->getSelectedYear();

        if (! $year) return $query; // no filter — show all

        return $query->where(function ($q) use ($year) {
            // New registrants this year
            $q->whereYear('users.created_at', $year);

            // OR: active candidates from previous years (continuing cohort)
            if ($year === now()->year) {
                $q->orWhere(function ($q2) use ($year) {
                    $q2->whereYear('users.created_at', '<', $year)
                        ->whereNull('program_completed_at');
                });
            }
        });
    }

    /**
     * Scope a Submission query by the year of submission.
     * For "current year" also includes submissions by active continuing candidates.
     */
    protected function scopeSubmissionsByYear(Builder $query, ?int $year = null): Builder
    {
        $year ??= $this->getSelectedYear();

        if (! $year) return $query;

        if ($year === now()->year) {
            // This year's submissions + submissions by still-active previous-year candidates
            return $query->where(function ($q) use ($year) {
                $q->whereYear('submissions.submitted_at', $year)
                    ->orWhereHas('student', function ($sq) use ($year) {
                        $sq->whereYear('users.created_at', '<', $year)
                            ->whereNull('program_completed_at');
                    });
            });
        }

        return $query->whereYear('submissions.submitted_at', $year);
    }

    /**
     * Scope a ProgramEnrollment query by the program's year.
     */
    protected function scopeEnrollmentsByYear(Builder $query, ?int $year = null): Builder
    {
        $year ??= $this->getSelectedYear();

        if (! $year) return $query;

        return $query->whereHas('trainingProgram', function ($q) use ($year) {
            $q->where('year', $year);
        });
    }
}
