<?php

namespace App\Models;

use App\Enums\ReviewModificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_completed'  => 'boolean',
            'reviewed_at'   => 'datetime',
            'admin_override'=> 'boolean',
            'overridden_at' => 'datetime',
            'score'         => 'decimal:1',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id')->withDefault([
            'name' => 'Not Assigned',
        ]);
    }

    public function overriddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }

    public function reviewRubrics(): HasMany
    {
        return $this->hasMany(ReviewRubric::class);
    }

    public function modificationRequests(): HasMany
    {
        return $this->hasMany(ReviewModificationRequest::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_completed', false);
    }

    public function scopeWithScore($query)
    {
        return $query->whereNotNull('score');
    }

    public function scopeForReviewer($query, int $reviewerId)
    {
        return $query->where('reviewer_id', $reviewerId);
    }

    // ── Accessors ──────────────────────────────────────────────────────────────

    /**
     * FIX: Added null-safety guards. Previously $this->submission->task->max_score
     * would throw a fatal error if submission or task was not loaded.
     * Now returns 0.0 safely when either relationship is missing.
     */
    public function getPercentageAttribute(): float
    {
        $maxScore = $this->submission?->task?->max_score ?? 10;

        if (! $this->score || $maxScore <= 0) {
            return 0.0;
        }

        return round(((float) $this->score / (float) $maxScore) * 100, 2);
    }

    // ── Modification Request Helpers ───────────────────────────────────────────

    public function hasApprovedModificationRequest(): bool
    {
        return $this->modificationRequests()
            ->where('status', ReviewModificationStatus::APPROVED->value)
            ->whereNull('used_at')
            ->exists();
    }

    public function hasPendingModificationRequest(): bool
    {
        return $this->modificationRequests()
            ->where('status', ReviewModificationStatus::PENDING->value)
            ->exists();
    }

    public function latestModificationRequest(): ?ReviewModificationRequest
    {
        return $this->modificationRequests()->latest()->first();
    }

    public function getActiveModificationRequest(): ?ReviewModificationRequest
    {
        return $this->modificationRequests()
            ->where('status', ReviewModificationStatus::APPROVED->value)
            ->whereNull('used_at')
            ->latest()
            ->first();
    }

    public function consumeModificationRequest(): void
    {
        $this->getActiveModificationRequest()?->markAsUsed();
    }

    // ── Lock / Modify State ────────────────────────────────────────────────────

    public function canBeModified(): bool
    {
        if (! $this->is_completed) {
            return true;
        }

        return $this->hasApprovedModificationRequest();
    }

    public function isLocked(): bool
    {
        return $this->is_completed && ! $this->hasApprovedModificationRequest();
    }

    // ── Rubric Methods ─────────────────────────────────────────────────────────

    /**
     * Total points from checked rubric entries only.
     */
    public function getTotalRubricScore(): float
    {
        return (float) $this->reviewRubrics()
            ->where('is_checked', true)
            ->sum('points_awarded');
    }

    /**
     * FIX: Now calls task->activeRubrics() (not rubrics()) so inactive rubrics
     * are excluded from the count, matching what the reviewer actually sees.
     * Previously used rubrics() which (before Task model fix) had is_active
     * baked in — but that was on the Task model, not via this method.
     */
    public function hasAllRubricsEvaluated(): bool
    {
        $taskRubricsCount  = $this->submission?->task?->activeRubrics()->count() ?? 0;
        $reviewRubricsCount = $this->reviewRubrics()->count();

        return $taskRubricsCount === $reviewRubricsCount;
    }

    public function getRubricCompletionPercentage(): float
    {
        $totalRubrics = $this->submission?->task?->activeRubrics()->count() ?? 0;

        if ($totalRubrics === 0) {
            return 100.0;
        }

        return round(($this->reviewRubrics()->count() / $totalRubrics) * 100, 2);
    }

    /**
     * Full rubric-by-rubric breakdown. Used by RubricManagementService
     * and reviewer grading views.
     */
    public function getRubricsSummary(): array
    {
        $taskRubrics   = $this->submission?->task?->activeRubrics ?? collect();
        $reviewRubrics = $this->reviewRubrics->keyBy('rubric_id');

        $totalPossible = $taskRubrics->sum('max_points');
        $totalAwarded  = 0;
        $checkedCount  = 0;
        $rubricDetails = [];

        foreach ($taskRubrics as $taskRubric) {
            $reviewRubric  = $reviewRubrics->get($taskRubric->id);
            $isChecked     = $reviewRubric?->is_checked ?? false;
            $pointsAwarded = $isChecked ? ((float) ($reviewRubric?->points_awarded ?? 0)) : 0;

            if ($isChecked) {
                $totalAwarded += $pointsAwarded;
                $checkedCount++;
            }

            $rubricDetails[] = [
                'task_rubric'   => $taskRubric,
                'review_rubric' => $reviewRubric,
                'is_checked'    => $isChecked,
                'points_awarded'=> $pointsAwarded,
                'percentage'    => $taskRubric->max_points > 0
                    ? round(($pointsAwarded / (float) $taskRubric->max_points) * 100, 2)
                    : 0.0,
            ];
        }

        $totalCount = $taskRubrics->count();

        return [
            'total_possible'       => $totalPossible,
            'total_awarded'        => $totalAwarded,
            'checked_count'        => $checkedCount,
            'total_count'          => $totalCount,
            'completion_percentage'=> $totalCount > 0
                ? round(($checkedCount / $totalCount) * 100, 2)
                : 100.0,
            'score_percentage'     => $totalPossible > 0
                ? round(($totalAwarded / (float) $totalPossible) * 100, 2)
                : 0.0,
            'rubric_details'       => $rubricDetails,
        ];
    }

    /**
     * FIX: Now uses activeRubrics() so only rubrics that are currently active
     * are created. Avoids creating ReviewRubric rows for rubrics that have been
     * deactivated after a review was started.
     */
    public function syncRubrics(): void
    {
        $taskRubrics = $this->submission?->task?->activeRubrics ?? collect();

        foreach ($taskRubrics as $taskRubric) {
            ReviewRubric::firstOrCreate(
                [
                    'review_id' => $this->id,
                    'rubric_id' => $taskRubric->id,
                ],
                [
                    'is_checked'    => false,
                    'points_awarded'=> 0,
                    'comments'      => null,
                ]
            );
        }
    }

    /**
     * Return manual score if set and non-zero, otherwise derive from rubrics.
     */
    public function getCalculatedScore(): float
    {
        if ($this->score && (float) $this->score > 0) {
            return (float) $this->score;
        }

        return $this->getTotalRubricScore();
    }

    public function isScoreConsistent(): bool
    {
        if (! $this->score) {
            return true;
        }

        return abs((float) $this->score - $this->getTotalRubricScore()) < 0.01;
    }

    public function getScoreDiscrepancy(): ?array
    {
        $manualScore = (float) ($this->score ?? 0);
        $rubricScore = $this->getTotalRubricScore();
        $difference  = abs($manualScore - $rubricScore);

        if ($difference < 0.01) {
            return null;
        }

        return [
            'manual_score'   => $manualScore,
            'rubric_score'   => $rubricScore,
            'difference'     => $difference,
            'percentage_diff'=> $manualScore > 0
                ? round(($difference / $manualScore) * 100, 2)
                : 0.0,
        ];
    }
}
