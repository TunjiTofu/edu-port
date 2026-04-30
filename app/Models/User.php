<?php

namespace App\Models;

use App\Enums\RoleTypes;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at'        => 'datetime',
            'password'                 => 'hashed',
            'is_active'                => 'boolean',
            'password_updated_at'      => 'datetime',
            'profile_completed_at'     => 'datetime',
            'program_completed_at'     => 'datetime',
            'disqualified_at'          => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function role(): BelongsTo    { return $this->belongsTo(Role::class); }
    public function district(): BelongsTo { return $this->belongsTo(District::class); }
    public function church(): BelongsTo  { return $this->belongsTo(Church::class); }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'student_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    /** Alias kept for ReviewerPerformanceWidget withCount() compatibility */
    public function reviewsAsReviewer(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ProgramEnrollment::class, 'student_id');
    }

    // ── Role Checks ────────────────────────────────────────────────────────────

    public function isStudent(): bool  { return $this->role?->name === RoleTypes::STUDENT->value; }
    public function isReviewer(): bool { return $this->role?->name === RoleTypes::REVIEWER->value; }
    public function isAdmin(): bool    { return $this->role?->name === RoleTypes::ADMIN->value; }
    public function isObserver(): bool { return $this->role?->name === RoleTypes::OBSERVER->value; }

    public function hasPermission(string $permission): bool
    {
        return $this->role?->hasPermission($permission) ?? false;
    }

    // ── Filament Panel Access ──────────────────────────────────────────────────

    /**
     * Filament calls this on every authenticated request.
     * Three conditions block access — any one is sufficient:
     *   1. Account deactivated by admin (is_active = false)
     *   2. Candidate disqualified (disqualified_at is set) — total login block
     *   3. Wrong role for the panel being accessed
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Deactivated accounts cannot access any panel
        if (! $this->is_active) return false;

        // Disqualified candidates cannot log in at all
        if ($this->isDisqualified()) return false;

        return match ($panel->getId()) {
            'admin'    => $this->isAdmin(),
            'reviewer' => $this->isReviewer(),
            'observer' => $this->isObserver(),
            'student'  => $this->isStudent(),
            default    => false,
        };
    }

    // ── Password Helpers ───────────────────────────────────────────────────────

    public function needsPasswordChange(): bool { return is_null($this->password_updated_at); }

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

    // ── Program Completion (Graduation) ────────────────────────────────────────

    /**
     * True when an admin has marked this candidate as having completed
     * the program. Graduated candidates are read-only — they cannot submit,
     * edit their profile, or enroll in new programs.
     */
    public function hasCompletedProgram(): bool
    {
        return ! is_null($this->program_completed_at);
    }

    /**
     * Mark candidate as having completed the program.
     * Called by admin via the UserResource "Mark as Graduated" action.
     */
    public function markProgramCompleted(): void
    {
        $this->update(['program_completed_at' => now()]);
    }

    /**
     * Reverse graduation — admin can unlock a candidate if marked by mistake.
     */
    public function unmarkProgramCompleted(): void
    {
        $this->update(['program_completed_at' => null]);
    }

    // ── Disqualification ───────────────────────────────────────────────────────

    /**
     * A disqualified candidate CANNOT log in at all.
     * Harder than deactivation (is_active=false) in that it carries a reason
     * and is intended to be temporary — the candidate can be restored once
     * they meet requirements.
     *
     * Disqualification vs deactivation:
     *   - is_active=false  → general account suspension, no reason required
     *   - disqualified_at  → formal program disqualification with a stated reason
     */
    public function isDisqualified(): bool
    {
        return ! is_null($this->disqualified_at);
    }

    /**
     * Disqualify a candidate. Also deactivates the account so both checks
     * fail independently — reverting disqualification also restores access.
     */
    public function disqualify(string $reason): void
    {
        $this->update([
            'disqualified_at'          => now(),
            'disqualification_reason'  => $reason,
            'is_active'                => false, // Belt and braces — blocks login even if disqualified_at check is skipped
        ]);
    }

    /**
     * Restore a disqualified candidate.
     * Re-activates the account so they can log in again.
     */
    public function undisqualify(): void
    {
        $this->update([
            'disqualified_at'          => null,
            'disqualification_reason'  => null,
            'is_active'                => true,
        ]);
    }

    /**
     * Returns a usable URL for the passport photo.
     * Cached for 23 hrs on S3 (same pattern as TrainingProgram::image_url).
     */
    public function getPassportPhotoUrlAttribute(): string
    {
        // asset('storage/...') is the most reliable URL on cPanel hosting.
        // It always resolves relative to APP_URL, unlike Storage::disk()->url()
        // which can produce wrong URLs in subdomain or non-standard setups.
        $default = asset('storage/passport-photos/default-avatar.jpg');

        if (! $this->passport_photo) {
            return $default;
        }

        try {
            if (config('filesystems.default') === 's3') {
                return Cache::remember(
                    'passport_photo_url_' . md5($this->passport_photo),
                    now()->addHours(23),
                    fn () => Storage::disk('s3')->temporaryUrl($this->passport_photo, now()->addHours(24))
                );
            }

            return asset('storage/' . $this->passport_photo);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('passport_photo_url failed', [
                'user_id' => $this->id,
                'error'   => $e->getMessage(),
            ]);
            return $default;
        }
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->passport_photo ? $this->passport_photo_url : null;
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeActive($query)        { return $query->where('is_active', true); }
    public function scopeStudents($query)      { return $query->byRole(RoleTypes::STUDENT->value); }
    public function scopeReviewers($query)     { return $query->byRole(RoleTypes::REVIEWER->value); }
    public function scopeByRole($query, string $roleName)
    {
        return $query->whereHas('role', fn ($q) => $q->where('name', $roleName));
    }
}
