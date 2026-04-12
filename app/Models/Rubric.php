<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rubric extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'max_points'  => 'decimal:2',
            'is_active'   => 'boolean',
            'order_index' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function reviewRubrics(): HasMany
    {
        return $this->hasMany(ReviewRubric::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index');
    }

    public function scopeForTask($query, int $taskId)
    {
        return $query->where('task_id', $taskId);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Check if this rubric has been used in any review.
     * Used by deletion guards to prevent removing rubrics that have
     * existing review_rubric records attached.
     */
    public function hasReviews(): bool
    {
        return $this->reviewRubrics()->exists();
    }

    /**
     * Average points awarded across all review_rubric entries for this rubric.
     * Used by RubricManagementService::getRubricPerformanceStats().
     */
    public function getAveragePointsAwarded(): float
    {
        return (float) $this->reviewRubrics()
            ->where('is_checked', true)
            ->avg('points_awarded') ?? 0.0;
    }
}
