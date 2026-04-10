<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimilarityCheck extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'similarity_percentage' => 'decimal:2',
            'matched_segments'      => 'array',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function submission1(): BelongsTo
    {
        return $this->belongsTo(Submission::class, 'submission_1_id');
    }

    public function submission2(): BelongsTo
    {
        return $this->belongsTo(Submission::class, 'submission_2_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    /**
     * Checks where similarity meets or exceeds the given threshold percentage.
     * Default is 70% — typical plagiarism review trigger point.
     */
    public function scopeAboveThreshold($query, float $threshold = 70.0)
    {
        return $query->where('similarity_percentage', '>=', $threshold);
    }

    public function scopeInvolvingSubmission($query, int $submissionId)
    {
        return $query->where('submission_1_id', $submissionId)
            ->orWhere('submission_2_id', $submissionId);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Whether this check's similarity exceeds the plagiarism threshold.
     */
    public function isFlagged(float $threshold = 70.0): bool
    {
        return (float) $this->similarity_percentage >= $threshold;
    }

    /**
     * Human-readable severity label based on similarity percentage.
     */
    public function getSeverityLabel(): string
    {
        $pct = (float) $this->similarity_percentage;

        return match (true) {
            $pct >= 80 => 'High',
            $pct >= 50 => 'Medium',
            default => 'Low',
        };
    }
}
