<?php

namespace App\Models;

use App\Enums\ProgramEnrollmentStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TrainingProgram extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'registration_deadline' => 'date',
            'is_active' => 'boolean',
            'max_students' => 'integer',
            'passing_score' => 'decimal:2',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('order_index');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ProgramEnrollment::class);
    }

    /**
     * All enrolled students via the program_enrollments pivot.
     * Use enrollments() HasMany for status filtering, counts, and dates.
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'program_enrollments',
            'training_program_id',
            'student_id'
        )->withTimestamps();
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRegistrationOpen($query)
    {
        return $query->active()
            ->whereNotNull('registration_deadline')
            ->where('registration_deadline', '>=', now()->startOfDay());
    }

    public function scopeNotEnrolledBy($query, int $studentId)
    {
        return $query->whereDoesntHave('enrollments', function ($q) use ($studentId) {
            $q->where('student_id', $studentId);
        });
    }

    // ── Accessors ──────────────────────────────────────────────────────────────

    /**
     * Human-readable program duration string.
     * e.g. "12 weeks 3 days"
     */
    public function getDurationWeeksAttribute(): string
    {
        if (! $this->start_date || ! $this->end_date) {
            return '0 weeks';
        }

        $days          = $this->start_date->diffInDays($this->end_date);
        $weeks         = (int) floor($days / 7);
        $remainingDays = $days % 7;

        $result = $weeks . ' week' . ($weeks !== 1 ? 's' : '');

        if ($remainingDays > 0) {
            $result .= ' ' . $remainingDays . ' day' . ($remainingDays !== 1 ? 's' : '');
        }

        return $result;
    }

    /**
     * FIX: S3 temporary URLs are now cached for 23 hours, reducing S3 API
     * calls from one-per-access to one-per-day. Previously, every reference
     * to $program->image_url (including in Blade loops) triggered an S3 API
     * call — 20 programs = 20 API calls per page load.
     *
     * Cache key includes a hash of the path so renamed/replaced images
     * automatically get a fresh URL rather than serving stale ones.
     */
    public function getImageUrlAttribute(): string
    {
        if (! $this->image) {
            return '/images/default-program.png';
        }

        try {
            if (config('filesystems.default') === 's3') {
                $cacheKey = 'program_image_url_' . md5($this->image);

                return Cache::remember($cacheKey, now()->addHours(23), function () {
                    return Storage::disk('s3')->temporaryUrl(
                        $this->image,
                        now()->addHours(24)
                    );
                });
            }

            return Storage::disk('public')->url($this->image);

        } catch (\Exception $e) {
            Log::error('Failed to generate training program image URL', [
                'program_id' => $this->id,
                'image_path' => $this->image,
                'error'      => $e->getMessage(),
            ]);

            return '/images/default-program.png';
        }
    }

    // ── Domain Methods ─────────────────────────────────────────────────────────

    /**
     * Check if this program is currently accepting new enrollments.
     * Compares against end-of-day so the deadline day is fully included.
     */
    public function isRegistrationOpen(): bool
    {
        $deadline = $this->registration_deadline;

        if (! $deadline) {
            return false;
        }

        return now()->lessThanOrEqualTo(
            Carbon::parse($deadline)->endOfDay()
        );
    }

    /**
     * Check if this program itself is active (not the enrollment status).
     */
    public function isActive(): bool
    {
        return (bool) ($this->attributes['is_active'] ?? false);
    }

    /**
     * FIX: Removed isCompleted() and isPaused() — these methods checked
     * $this->status which does not exist as a column on training_programs.
     * The status field belongs to ProgramEnrollment, not the program itself.
     *
     * To check a student's enrollment status for this program, use:
     *   $program->enrollments()->forStudent($studentId)->first()?->status
     * or:
     *   ProgramEnrollment::where('student_id', $id)
     *       ->where('training_program_id', $this->id)->first()?->isCompleted()
     */

    /**
     * Check if the program has available capacity.
     * Returns true if max_students is null (unlimited) or not yet reached.
     */
    public function hasAvailableCapacity(): bool
    {
        if (is_null($this->max_students)) {
            return true;
        }

        return $this->enrollments()->count() < $this->max_students;
    }

    /**
     * Clear the cached image URL for this program.
     * Call this after updating the program image.
     */
    public function clearImageCache(): void
    {
        if ($this->image) {
            Cache::forget('program_image_url_' . md5($this->image));
        }
    }
}
