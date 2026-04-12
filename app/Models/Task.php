<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active'   => 'boolean',
            'due_date'    => 'datetime',
            'max_score'   => 'decimal:1',
            'order_index' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class)->orderBy('submitted_at', 'desc');
    }

    /**
     * FIX: Removed the where('is_active', true) filter from the base relationship.
     *
     * Previously this filter was baked in, which silently hid inactive rubrics
     * from the admin RubricResource and RubricsRelationManager — even in contexts
     * where the admin needs to see all rubrics. Use activeRubrics() in student-
     * facing contexts and rubrics() everywhere else.
     */
    public function rubrics(): HasMany
    {
        return $this->hasMany(Rubric::class)->orderBy('order_index');
    }

    /**
     * Active rubrics only — use this in student-facing and reviewer-facing
     * grading views where inactive rubrics should not appear.
     */
    public function activeRubrics(): HasMany
    {
        return $this->hasMany(Rubric::class)
            ->where('is_active', true)
            ->orderBy('order_index');
    }

    /**
     * The single result publication record for this task.
     * Use this when you need to check/update publish status for one task.
     */
    public function resultPublication(): HasOne
    {
        return $this->hasOne(ResultPublication::class);
    }

    /**
     * All result publication records for this task.
     * Used by admin resource listing — normally there should only be one,
     * but the unique constraint is on (task_id, published_by) not task_id alone.
     */
    public function resultPublications(): HasMany
    {
        return $this->hasMany(ResultPublication::class);
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

    public function scopeOverdue($query)
    {
        return $query->where('is_active', true)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('is_active', true)
            ->where('due_date', '>=', now());
    }

    public function scopeForSection($query, int $sectionId)
    {
        return $query->where('section_id', $sectionId);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * FIX: Renamed from isResultsPublished() (plural) to isResultPublished()
     * to match what Submission::isResultPublished() calls. Both the singular
     * and plural forms are now handled.
     */
    public function isResultPublished(): bool
    {
        return $this->resultPublications()
            ->where('is_published', true)
            ->exists();
    }

    /** @deprecated Use isResultPublished() */
    public function isResultsPublished(): bool
    {
        return $this->isResultPublished();
    }

    public function getPublishedResult(): ?ResultPublication
    {
        return $this->resultPublications()
            ->where('is_published', true)
            ->first();
    }

    /**
     * Sum of max_points across all ACTIVE rubrics.
     * Used by TaskResource table column and validation checks.
     */
    public function getTotalRubricPoints(): float
    {
        return (float) $this->activeRubrics()->sum('max_points');
    }

    /**
     * Check if this task is past its due date.
     */
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast();
    }

    /**
     * Check if a specific student has submitted for this task.
     */
    public function hasSubmissionFrom(int $studentId): bool
    {
        return $this->submissions()->where('student_id', $studentId)->exists();
    }
}
