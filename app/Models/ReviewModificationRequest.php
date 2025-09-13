<?php

namespace App\Models;

use App\Enums\ReviewModificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewModificationRequest extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'approved_at' => 'datetime',
        'used_at' => 'datetime', // Add this new field
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function isPending(): bool
    {
        return $this->status === ReviewModificationStatus::PENDING->value;
    }

    public function isApproved(): bool
    {
        return $this->status === ReviewModificationStatus::APPROVED->value;
    }

    public function isRejected(): bool
    {
        return $this->status === ReviewModificationStatus::REJECTED->value;
    }

    /**
     * Check if this approved request has been used
     */
    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    /**
     * Check if this is an active (unused) approved request
     */
    public function isActive(): bool
    {
        return $this->isApproved() && !$this->isUsed();
    }

    /**
     * Mark this modification request as used
     */
    public function markAsUsed(): void
    {
        if ($this->isApproved() && !$this->isUsed()) {
            $this->update(['used_at' => now()]);
        }
    }

    public function approve(User $admin, ?string $comments = null): void
    {
        $this->update([
            'status' => 'approved',
            'admin_id' => $admin->id,
            'admin_comments' => $comments,
            'approved_at' => now(),
        ]);
    }

    public function reject(User $admin, ?string $comments = null): void
    {
        $this->update([
            'status' => 'rejected',
            'admin_id' => $admin->id,
            'admin_comments' => $comments,
        ]);
    }
}
