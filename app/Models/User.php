<?php

namespace App\Models;

use App\Enums\RoleTypes;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        // FIX: Removed duplicate reviewsAsReviewer() method — identical to this one.
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
