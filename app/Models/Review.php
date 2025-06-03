<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

}
