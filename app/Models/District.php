<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class District extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function churches(): HasMany
    {
        return $this->hasMany(Church::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithUsers($query)
    {
        return $query->has('users');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Used by deletion guards in DistrictResource to prevent
     * removing districts with live user accounts.
     */
    public function hasUsers(): bool
    {
        return $this->users()->exists();
    }

    public function hasChurches(): bool
    {
        return $this->churches()->exists();
    }
}
