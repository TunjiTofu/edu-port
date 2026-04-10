<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Section extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active'   => 'boolean',
            'order_index' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('order_index');
    }

    /**
     * Active tasks only — used by student-facing views.
     * Does not filter at the base relationship level so admin resources
     * can still access all tasks via tasks().
     */
    public function activeTasks(): HasMany
    {
        return $this->hasMany(Task::class)
            ->where('is_active', true)
            ->orderBy('order_index');
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

    public function scopeForProgram($query, int $programId)
    {
        return $query->where('training_program_id', $programId);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Check if any task in this section has a pending submission
     * for the given student. Used by progress calculations.
     */
    public function hasPendingTasksFor(int $studentId): bool
    {
        return $this->tasks()
            ->whereDoesntHave('submissions', fn ($q) => $q->where('student_id', $studentId))
            ->where('is_active', true)
            ->exists();
    }
}
