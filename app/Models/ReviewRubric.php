<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewRubric extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'points_awarded' => 'decimal:2',
            'is_checked'     => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(Rubric::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeChecked($query)
    {
        return $query->where('is_checked', true);
    }

    public function scopeUnchecked($query)
    {
        return $query->where('is_checked', false);
    }

    public function scopeForReview($query, int $reviewId)
    {
        return $query->where('review_id', $reviewId);
    }

    public function scopeForRubric($query, int $rubricId)
    {
        return $query->where('rubric_id', $rubricId);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Award points for this rubric entry and mark it as checked.
     */
    public function award(float $points, ?string $comments = null): void
    {
        $this->update([
            'is_checked'     => true,
            'points_awarded' => $points,
            'comments'       => $comments,
        ]);
    }

    /**
     * Get awarded points as a percentage of maximum possible points.
     */
    public function getAwardedPercentage(): float
    {
        $max = (float) ($this->rubric?->max_points ?? 0);

        if ($max <= 0) {
            return 0.0;
        }

        return round(((float) $this->points_awarded / $max) * 100, 2);
    }
}
