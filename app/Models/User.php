<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\RoleTypes;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean'
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function district() : BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function church() : BelongsTo
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

    public function enrollments(): HasMany
    {
        return $this->hasMany(ProgramEnrollment::class, 'student_id');
    }

    public function isStudent() : bool
    {
        return $this->role->name === RoleTypes::STUDENT->value;
    }

    public function isReviewer() : bool
    {
        return $this->role->name === RoleTypes::REVIEWER->value;
    }

    public function isAdmin() : bool
    {
        return $this->role->name === RoleTypes::ADMIN->value;
    }

    public function isObserver() : bool
    {
        return $this->role->name === RoleTypes::OBSERVER->value;
    }

    public function hasPermission(string $permission): bool
    {
        return $this->role->hasPermission($permission);
    }

    public function reviewsAsReviewer()
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }
}
