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
        'used_at'     => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

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

    // ── State Checks ───────────────────────────────────────────────────────────

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

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    /**
     * An active request is one that has been approved but not yet consumed
     * by a reviewer edit.
     */
    public function isActive(): bool
    {
        return $this->isApproved() && ! $this->isUsed();
    }

    /**
     * Consume this modification token after the reviewer has made their edit.
     * No-op if already used or not approved.
     */
    public function markAsUsed(): void
    {
        if ($this->isApproved() && ! $this->isUsed()) {
            $this->update(['used_at' => now()]);
        }
    }

    // ── Admin Actions ──────────────────────────────────────────────────────────

    /**
     * FIX: approve() now uses ReviewModificationStatus::APPROVED->value
     * instead of the hardcoded string 'approved'. If the enum value ever
     * changes, this stays consistent with the rest of the codebase.
     */
    public function approve(User $admin, ?string $comments = null): void
    {
        $this->update([
            'status'         => ReviewModificationStatus::APPROVED->value,
            'admin_id'       => $admin->id,
            'admin_comments' => $comments,
            'approved_at'    => now(),
        ]);
    }

    /**
     * FIX: reject() now uses ReviewModificationStatus::REJECTED->value
     * instead of the hardcoded string 'rejected'.
     */
    public function reject(User $admin, ?string $comments = null): void
    {
        $this->update([
            'status'         => ReviewModificationStatus::REJECTED->value,
            'admin_id'       => $admin->id,
            'admin_comments' => $comments,
        ]);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', ReviewModificationStatus::PENDING->value);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', ReviewModificationStatus::APPROVED->value);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', ReviewModificationStatus::REJECTED->value);
    }

    public function scopeUnused($query)
    {
        return $query->whereNull('used_at');
    }

    public function scopeActive($query)
    {
        return $query->approved()->unused();
    }
}
