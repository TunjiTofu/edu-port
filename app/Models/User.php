<?php

namespace App\Models;

use App\Enums\RoleTypes;
use Cache;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Storage;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'password'           => 'hashed',
            'is_active'          => 'boolean',
            'password_updated_at'=> 'datetime',
            'profile_completed_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'student_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    /**
     * Alias of reviews() kept for withCount() compatibility.
     * ReviewerPerformanceWidget uses this name in withCount()/withAvg() calls:
     *   ->withCount(['reviewsAsReviewer as total_reviews', ...])
     *   ->withAvg('reviewsAsReviewer as avg_score', 'score')
     * Renaming the relationship would break those aliases.
     */
    public function reviewsAsReviewer(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ProgramEnrollment::class, 'student_id');
    }

    // ── Role Checks ────────────────────────────────────────────────────────────
    // FIX: All role checks now use null-safe operator (?->).
    //      Previously $this->role->name would throw a fatal error for any user
    //      whose role relationship is null (e.g. orphaned users, eager-load gaps).

    public function isStudent(): bool
    {
        return $this->role?->name === RoleTypes::STUDENT->value;
    }

    public function isReviewer(): bool
    {
        return $this->role?->name === RoleTypes::REVIEWER->value;
    }

    public function isAdmin(): bool
    {
        return $this->role?->name === RoleTypes::ADMIN->value;
    }

    public function isObserver(): bool
    {
        return $this->role?->name === RoleTypes::OBSERVER->value;
    }

    public function hasPermission(string $permission): bool
    {
        return $this->role?->hasPermission($permission) ?? false;
    }

    // ── Filament Access Control ────────────────────────────────────────────────

    /**
     * FIX: canAccessPanel() was returning true for ALL roles on ALL panels.
     * This gave every user a pass on every panel, relying entirely on the
     * role-enforcement middleware. Now each panel checks the correct role,
     * providing a second layer of defence.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return match ($panel->getId()) {
            'admin'    => $this->isAdmin(),
            'reviewer' => $this->isReviewer(),
            'observer' => $this->isObserver(),
            'student'  => $this->isStudent(),
            default    => false,
        };
    }

    // ── Password Helpers ───────────────────────────────────────────────────────

    /**
     * Check if user has never changed their default password.
     */
    public function needsPasswordChange(): bool
    {
        return is_null($this->password_updated_at);
    }

    /**
     * Mark the password as having been changed by the user.
     * Call this after a successful password update instead of manually
     * setting password_updated_at in every controller/page.
     */
    public function markPasswordAsChanged(): void
    {
        $this->update(['password_updated_at' => now()]);
    }

    // ── Profile Completion ─────────────────────────────────────────────────────

    /**
     * A candidate's profile is considered complete when they have:
     *  - phone number
     *  - church assigned
     *  - district assigned
     *  - passport photo uploaded
     *
     * Used by EnsureProfileComplete middleware to redirect incomplete profiles.
     */
    public function isProfileComplete(): bool
    {
        return ! empty($this->phone)
            && ! is_null($this->church_id)
            && ! is_null($this->district_id)
            && ! empty($this->passport_photo);
    }

    /**
     * Mark profile as completed, storing the timestamp.
     * Called after the candidate saves a complete profile in EditProfile.
     */
    public function markProfileComplete(): void
    {
        if ($this->isProfileComplete() && is_null($this->profile_completed_at)) {
            $this->update(['profile_completed_at' => now()]);
        }
    }

    // ── Passport Photo URL ─────────────────────────────────────────────────────

    /**
     * Returns a usable URL for the passport photo.
     * Cached for 23 hrs on S3 (same pattern as TrainingProgram::image_url).
     */
    public function getPassportPhotoUrlAttribute(): string
    {
        if (! $this->passport_photo) {
            return asset('images/default-avatar.png');
        }

        try {
            if (config('filesystems.default') === 's3') {
                return Cache::remember(
                    'passport_photo_url_' . md5($this->passport_photo),
                    now()->addHours(23),
                    fn () => Storage::disk('s3')->temporaryUrl($this->passport_photo, now()->addHours(24))
                );
            }

            return Storage::disk('public')->url($this->passport_photo);

        } catch (\Exception $e) {
            Log::error('Failed to generate passport photo URL', [
                'user_id' => $this->id,
                'error'   => $e->getMessage(),
            ]);

            return asset('images/default-avatar.png');
        }
    }

    /**
     * Filament reads this to render the avatar in the top-right user menu,
     * the user panel header, and any place Filament displays the current user.
     *
     * Implementing HasAvatar and returning a non-null value here is all that
     * is required — Filament handles the <img> rendering automatically.
     *
     * Returns null if no photo is set, which makes Filament fall back to its
     * default initials avatar so the navbar never shows a broken image.
     */
    public function getFilamentAvatarUrl(): ?string
    {
        if (! $this->passport_photo) {
            return null; // Filament will render initials instead
        }

        return $this->passport_photo_url; // reuse the cached accessor above
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, string $roleName)
    {
        return $query->whereHas('role', fn ($q) => $q->where('name', $roleName));
    }

    public function scopeStudents($query)
    {
        return $query->byRole(RoleTypes::STUDENT->value);
    }

    public function scopeReviewers($query)
    {
        return $query->byRole(RoleTypes::REVIEWER->value);
    }

    public function scopeObservers($query)
    {
        return $query->byRole(RoleTypes::OBSERVER->value);
    }

    public function scopeAdmins($query)
    {
        return $query->byRole(RoleTypes::ADMIN->value);
    }
}
