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
}
