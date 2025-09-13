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

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'reviewed_at' => 'datetime',
            'admin_override' => 'boolean',
            'overridden_at' => 'datetime',
            'score' => 'decimal:1',
        ];
    }

    /**
     * Get the submission that owns the review.
     *
     * @return BelongsTo
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    /**
     * Get the reviewer that owns the review.
     *
     * @return BelongsTo
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault([
            'name' => 'Not Assigned' // Default value if no reviewer
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

    // Get percentage score
    public function getPercentageAttribute(): float
    {
        $maxScore = $this->submission->task->max_score ?? 10;
        return ($this->score / $maxScore) * 100;
    }

    // Scope for completed reviews
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    // Scope for reviews with scores
    public function scopeWithScore($query)
    {
        return $query->whereNotNull('score');
    }

    public function modificationRequests(): HasMany
    {
        return $this->hasMany(ReviewModificationRequest::class);
    }

    public function hasApprovedModificationRequest(): bool
    {
        return $this->modificationRequests()
            ->where('status', ReviewModificationStatus::APPROVED->value)
            ->whereNull('used_at') // Only unused approvals
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
        return $this->modificationRequests()
            ->latest()
            ->first();
    }

    /**
     * Get the currently active (unused) approved modification request
     */
    public function getActiveModificationRequest(): ?ReviewModificationRequest
    {
        return $this->modificationRequests()
            ->where('status', ReviewModificationStatus::APPROVED->value)
            ->whereNull('used_at')
            ->latest()
            ->first();
    }

    /**
     * Mark the approved modification request as used
     */
    public function consumeModificationRequest(): void
    {
        $activeRequest = $this->getActiveModificationRequest();
        if ($activeRequest) {
            $activeRequest->markAsUsed();
        }
    }

    /**
     * Check if the review can be modified
     */
    public function canBeModified(): bool
    {
        // If not completed, can always be modified
        if (!$this->is_completed) {
            return true;
        }

        // If completed, can only be modified if there's an unused approved request
        return $this->hasApprovedModificationRequest();
    }

    /**
     * Check if the review is locked (completed and no active modification request)
     */
    public function isLocked(): bool
    {
        return $this->is_completed && !$this->hasApprovedModificationRequest();
    }

    // Calculate total rubric score
//    public function getTotalRubricScore(): float
//    {
//        return $this->reviewRubrics()->sum('points_awarded');
//    }

    // Check if all rubrics are evaluated
    public function hasAllRubricsEvaluated(): bool
    {
        $taskRubricsCount = $this->submission->task->rubrics()->count();
        $reviewRubricsCount = $this->reviewRubrics()->count();

        return $taskRubricsCount === $reviewRubricsCount;
    }

    /**
     * Calculate total points from rubrics
     */
    public function getTotalRubricScore(): float
    {
        return $this->reviewRubrics()
            ->where('is_checked', true)
            ->sum('points_awarded');
    }

    /**
     * Get rubric completion percentage
     */
    public function getRubricCompletionPercentage(): float
    {
        $totalRubrics = $this->submission->task->rubrics()->count();

        if ($totalRubrics === 0) {
            return 100; // No rubrics means 100% complete
        }

        $completedRubrics = $this->reviewRubrics()->count();

        return ($completedRubrics / $totalRubrics) * 100;
    }

    /**
     * Get rubrics summary
     */
    public function getRubricsSummary(): array
    {
        $taskRubrics = $this->submission->task->rubrics;
        $reviewRubrics = $this->reviewRubrics->keyBy('rubric_id');

        $totalPossible = $taskRubrics->sum('max_points');
        $totalAwarded = 0;
        $checkedCount = 0;
        $rubricDetails = [];

        foreach ($taskRubrics as $taskRubric) {
            $reviewRubric = $reviewRubrics->get($taskRubric->id);
            $isChecked = $reviewRubric?->is_checked ?? false;
            $pointsAwarded = $reviewRubric?->points_awarded ?? 0;

            if ($isChecked) {
                $totalAwarded += $pointsAwarded;
                $checkedCount++;
            }

            $rubricDetails[] = [
                'task_rubric' => $taskRubric,
                'review_rubric' => $reviewRubric,
                'is_checked' => $isChecked,
                'points_awarded' => $pointsAwarded,
                'percentage' => $taskRubric->max_points > 0 ?
                    ($pointsAwarded / $taskRubric->max_points) * 100 : 0,
            ];
        }

        return [
            'total_possible' => $totalPossible,
            'total_awarded' => $totalAwarded,
            'checked_count' => $checkedCount,
            'total_count' => $taskRubrics->count(),
            'completion_percentage' => $taskRubrics->count() > 0 ?
                ($checkedCount / $taskRubrics->count()) * 100 : 100,
            'score_percentage' => $totalPossible > 0 ?
                ($totalAwarded / $totalPossible) * 100 : 0,
            'rubric_details' => $rubricDetails,
        ];
    }

    /**
     * Sync rubrics with task rubrics (create missing ones)
     */
    public function syncRubrics(): void
    {
        $taskRubrics = $this->submission->task->rubrics;

        foreach ($taskRubrics as $taskRubric) {
            ReviewRubric::firstOrCreate(
                [
                    'review_id' => $this->id,
                    'rubric_id' => $taskRubric->id,
                ],
                [
                    'is_checked' => false,
                    'points_awarded' => 0,
                    'comments' => null,
                ]
            );
        }
    }

    /**
     * Auto-calculate score based on rubrics if no manual score is set
     */
    public function getCalculatedScore(): float
    {
        // If manual score is set, use it
        if ($this->score && $this->score > 0) {
            return $this->score;
        }

        // Otherwise, calculate from rubrics
        return $this->getTotalRubricScore();
    }

    /**
     * Check if rubric score matches manual score
     */
    public function isScoreConsistent(): bool
    {
        if (!$this->score) {
            return true; // No manual score to compare
        }

        $rubricScore = $this->getTotalRubricScore();
        return abs($this->score - $rubricScore) < 0.01; // Allow for small floating point differences
    }

    /**
     * Get score discrepancy information
     */
    public function getScoreDiscrepancy(): ?array
    {
        $manualScore = $this->score ?? 0;
        $rubricScore = $this->getTotalRubricScore();
        $difference = abs($manualScore - $rubricScore);

        if ($difference < 0.01) {
            return null; // No significant discrepancy
        }

        return [
            'manual_score' => $manualScore,
            'rubric_score' => $rubricScore,
            'difference' => $difference,
            'percentage_diff' => $manualScore > 0 ? ($difference / $manualScore) * 100 : 0,
        ];
    }
}
