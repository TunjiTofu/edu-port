<?php

namespace App\Models;

use App\Enums\ProgramEnrollmentStatus;
use App\Helpers\TrainingProgramHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class TrainingProgram extends Model
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
     * @var array<string, string>
     */
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

    /**
     *
     * @return HasMany
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('order_index');
    }

    /**
     * Get the students enrolled in the training program.
     *
     * @return BelongsToMany
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'program_enrollments', 'training_program_id', 'student_id')
            ->withTimestamps();
    }

    /**
     * Get the enrollments associated with the training program.
     *
     * @return HasMany
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(ProgramEnrollment::class);
    }

    public function getDurationWeeksAttribute(): string
    {
        if (!$this->start_date || !$this->end_date) {
            return '0 weeks';
        }

        $days = $this->start_date->diffInDays($this->end_date);
        $weeks = floor($days / 7);
        $remainingDays = $days % 7;

        $result = $weeks . ' week' . ($weeks != 1 ? 's' : '');

        if ($remainingDays > 0) {
            $result .= ' ' . $remainingDays . ' day' . ($remainingDays != 1 ? 's' : '');
        }

        return $result;
    }

    /**
     * Check if the enrollment is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->attributes['is_active'] ?? false;
    }

    public function isRegistrationOpen(): bool
    {
        $registrationDeadline = $this->attributes['registration_deadline'] ?? null;
        if (!$registrationDeadline) {
            return false;
        }

        // Ensure we compare with the END of the deadline day
        $deadline = Carbon::parse($registrationDeadline)->endOfDay();

        return now()->lessThanOrEqualTo($deadline);
    }

    /**
     * Check if the enrollment is completed.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === ProgramEnrollmentStatus::COMPLETED;
    }

    /**
     * Check if the enrollment is paused.
     *
     * @return bool
     */
    public function isPaused(): bool
    {
        return $this->status === ProgramEnrollmentStatus::PAUSED;
    }

    public function getImageUrlAttribute()
    {
        if ($this->image) {
            try {
                if (config('filesystems.default') === 's3') {
                    return Storage::disk('s3')->temporaryUrl(
                        $this->image,
                        now()->addHours(24)
                    );
                } else {
                    return Storage::disk('public')->url($this->image);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to generate image URL', [
                    'image_path' => $this->image,
                    'error' => $e->getMessage()
                ]);
                return '/images/default-program.png';
            }
        }

        return '/images/default-program.png'; // Default image path
    }
}
