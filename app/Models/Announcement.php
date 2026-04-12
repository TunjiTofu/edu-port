<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_published'  => 'boolean',
        'sent_email'    => 'boolean',
        'sent_sms'      => 'boolean',
        'published_at'  => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeForAudience($query, string $role)
    {
        return $query->where(function ($q) use ($role) {
            $q->where('audience', 'all')
                ->orWhere('audience', $role);
        });
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderByDesc('published_at');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function audienceLabel(): string
    {
        return match ($this->audience) {
            'all'       => 'Everyone',
            'candidate' => 'Candidates only',
            'reviewer'  => 'Reviewers only',
            'observer'  => 'Observers only',
            'admin'     => 'Admins only',
            default     => ucfirst($this->audience),
        };
    }
}
