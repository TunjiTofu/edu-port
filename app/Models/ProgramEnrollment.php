<?php

namespace App\Models;

use App\Enums\ProgramEnrollmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgramEnrollment extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'status'      => 'string',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', ProgramEnrollmentStatus::ACTIVE->value);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', ProgramEnrollmentStatus::COMPLETED->value);
    }

    public function scopePaused($query)
    {
        return $query->where('status', ProgramEnrollmentStatus::PAUSED->value);
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForProgram($query, int $programId)
    {
        return $query->where('training_program_id', $programId);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === ProgramEnrollmentStatus::ACTIVE->value;
    }

    public function isCompleted(): bool
    {
        return $this->status === ProgramEnrollmentStatus::COMPLETED->value;
    }

    public function isPaused(): bool
    {
        return $this->status === ProgramEnrollmentStatus::PAUSED->value;
    }
}
