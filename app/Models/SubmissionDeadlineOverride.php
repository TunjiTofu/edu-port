<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionDeadlineOverride extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_used' => 'boolean',
            'used_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if this override is still available (not yet consumed).
     */
    public function isActive(): bool
    {
        return ! $this->is_used;
    }

    /**
     * Mark the override as consumed after the student uses it.
     */
    public function markAsUsed(): void
    {
        $this->update(['is_used' => true, 'used_at' => now()]);
    }

    /**
     * Check whether a student has an active (unused) override for a task.
     */
    public static function hasActive(int $studentId, int $taskId): bool
    {
        return static::where('student_id', $studentId)
            ->where('task_id', $taskId)
            ->where('is_used', false)
            ->exists();
    }
}
