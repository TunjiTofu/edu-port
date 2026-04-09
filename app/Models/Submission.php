<?php

namespace App\Models;

use App\Enums\SubmissionTypes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Submission extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'file_size'    => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * The single primary review for this submission.
     * Use this in most contexts — one submission has one review.
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /**
     * All review records for this submission.
     * Used when accessing review history or admin override scenarios.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function similarityChecks(): HasMany
    {
        return $this->hasMany(SimilarityCheck::class, 'submission_1_id');
    }

    /**
     * Review rubrics for the currently authenticated reviewer.
     * Scoped to auth()->id() — only call this in reviewer-facing contexts.
     */
    public function currentReviewRubrics(): HasManyThrough
    {
        return $this->hasManyThrough(
            ReviewRubric::class,
            Review::class,
            'submission_id',
            'review_id',
            'id',
            'id'
        )->where('reviews.reviewer_id', auth()->id());
    }

    /**
     * Review rubrics for a specific reviewer.
     * Use this in admin contexts where you need a named reviewer's rubrics.
     */
    public function reviewRubricsForReviewer(int $reviewerId): HasManyThrough
    {
        return $this->hasManyThrough(
            ReviewRubric::class,
            Review::class,
            'submission_id',
            'review_id',
            'id',
            'id'
        )->where('reviews.reviewer_id', $reviewerId);
    }

    /**
     * The review belonging to the currently authenticated reviewer.
     */
    public function currentReview(): HasOne
    {
        return $this->hasOne(Review::class)->where('reviewer_id', auth()->id());
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePendingReview($query)
    {
        return $query->where('status', SubmissionTypes::PENDING_REVIEW->value);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', SubmissionTypes::COMPLETED->value);
    }

    public function scopeFlagged($query)
    {
        return $query->where('status', SubmissionTypes::FLAGGED->value);
    }

    public function scopeWithPublishedResults($query)
    {
        return $query->whereHas('task', fn ($q) =>
        $q->whereHas('resultPublications', fn ($rq) =>
        $rq->where('is_published', true)
        )
        );
    }

    // ── Accessors ──────────────────────────────────────────────────────────────

    /**
     * FIX: getFileUrl() now uses the configured filesystem disk instead of
     * always defaulting to the public disk via asset('storage/...'). This
     * was incorrect for S3-backed deployments.
     *
     * For S3, use the download route instead of a public URL:
     *   route('submission.download', $this)
     */
    public function getFileUrl(): string
    {
        if (config('filesystems.default') === 's3') {
            return route('submission.download', $this);
        }

        return Storage::disk('public')->url($this->file_path . '/' . $this->file_name);
    }

    /**
     * Proxies review score so templates can use $submission->score
     * without explicitly loading the review relationship first.
     * Returns null if no review exists or no score assigned yet.
     */
    public function getScoreAttribute(): ?float
    {
        return $this->review?->score;
    }

    /**
     * Proxies review comments for template convenience.
     * NOTE: 'comments' is a column on the reviews table, not submissions.
     *       $submission->student_notes is the candidate's own note.
     */
    public function getCommentsAttribute(): ?string
    {
        return $this->review?->comments;
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Check whether the result for this submission's task is published.
     * Delegates to Task::isResultPublished() to keep the logic in one place.
     */
    public function isResultPublished(): bool
    {
        return $this->task?->isResultPublished() ?? false;
    }

    /**
     * Return the full storage path for this submission file.
     * Separates the path-building concern from URL generation.
     */
    public function getStoragePath(): string
    {
        return $this->file_path . '/' . $this->file_name;
    }
}
