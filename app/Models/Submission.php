<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Submission extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $guarded = ['id'];

    protected $with = ['review'];

    /**
     * The attributes that should be cast to native types.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * Get the task that owns the submission.
     *
     * @return BelongsTo
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the student that owns the submission.
     *
     * @return BelongsTo
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function similarityChecks(): HasMany
    {
        return $this->hasMany(SimilarityCheck::class, 'submission_1_id');
    }

    public function getFileUrl(): string
    {
        return asset('storage/' . $this->file_path);
    }

    public function isResultPublished(): bool
    {
        return $this->task->isResultPublished();
    }

    public function getScoreAttribute()
    {
        return $this->review?->score;
    }

    public function getCommentsAttribute()
    {
        return $this->review?->comments;
    }

    // Add this method to access review rubrics for the current reviewer
    public function currentReviewRubrics()
    {
        return $this->hasManyThrough(
            ReviewRubric::class,
            Review::class,
            'submission_id', // Foreign key on reviews table
            'review_id',     // Foreign key on review_rubrics table
            'id',            // Local key on submissions table
            'id'             // Local key on reviews table
        )->where('reviews.reviewer_id', auth()->id());
    }

    // Alternative: Get review rubrics for a specific reviewer
    public function reviewRubricsForReviewer($reviewerId)
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

    // Get the review for the current user
    public function currentReview()
    {
        return $this->hasOne(Review::class)->where('reviewer_id', auth()->id());
    }
}
