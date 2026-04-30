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
            'start_date'            => 'date',
            'end_date'              => 'date',
            'registration_deadline' => 'date',
            'is_active'             => 'boolean',
            'max_students'          => 'integer',
            'passing_score'         => 'decimal:2',
            'year'                  => 'integer',
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
                return '/images/logo.png';
            }
        }

        return '/images/logo.png'; // Default image path
    }

    // ── Query Scopes ───────────────────────────────────────────────────────────

    /**
     * Only return active programs.
     * Used by AvailableTrainingProgramResource and the student panel.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Exclude programs the given user is already enrolled in.
     * Used by AvailableTrainingProgramResource to show only enrollable programs.
     */
    public function scopeNotEnrolledBy($query, int $userId)
    {
        return $query->whereDoesntHave('enrollments', function ($q) use ($userId) {
            $q->where('student_id', $userId);
        });
    }

    /**
     * Only return programs for a specific year.
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Returns true when the program still has capacity for new enrollments.
     * If max_students is null or 0 the program is considered unlimited.
     */
    public function hasAvailableCapacity(): bool
    {
        if (empty($this->max_students)) {
            return true; // no cap set
        }

        $enrolled = $this->enrollments()->count();
        return $enrolled < $this->max_students;
    }

    /**
     * Clone this program into a new program with a different name and year.
     * Copies all sections, tasks, and rubrics recursively.
     * The new program is created as INACTIVE so the admin can review it
     * before making it available to candidates.
     *
     * Called from the admin Site Settings page "Clone Program" action.
     *
     * @return static  The newly created program
     */
    public function cloneTo(string $newName, int $year): static
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($newName, $year) {

            // ── Clone the program record ──────────────────────────────────
            $newProgram = $this->replicate();
            $newProgram->name      = $newName;
            $newProgram->year      = $year;
            $newProgram->is_active = false; // Admin must activate explicitly

            // ── Generate a unique code ─────────────────────────────────────
            // replicate() copies the code verbatim, which violates the unique
            // constraint. Replace any 4-digit year in the original code with
            // the new year. If still not unique, append a numeric suffix.
            $sourceCode  = $this->code ?? '';
            $sourceYear  = (string) ($this->year ?? date('Y'));
            $newYearStr  = (string) $year;

            // e.g. "LD2024" → "LD2026"
            $candidate = $sourceCode
                ? str_replace($sourceYear, $newYearStr, $sourceCode)
                : strtoupper(substr(preg_replace('/\s+/', '', $newName), 0, 4)) . $newYearStr;

            // Ensure uniqueness — append suffix if needed
            $suffix = 1;
            $unique = $candidate;
            while (static::withTrashed()->where('code', $unique)->exists()) {
                $unique = $candidate . '_' . $suffix++;
            }

            $newProgram->code = $unique;
            $newProgram->save();

            // ── Clone sections → tasks → rubrics ─────────────────────────
            $this->sections()->with('tasks.rubrics')->get()
                ->each(function ($section) use ($newProgram) {

                    $newSection = $section->replicate();
                    $newSection->training_program_id = $newProgram->id;
                    $newSection->save();

                    $section->tasks->each(function ($task) use ($newSection) {

                        $newTask = $task->replicate();
                        $newTask->section_id = $newSection->id;
                        $newTask->save();

                        $task->rubrics->each(function ($rubric) use ($newTask) {
                            $newRubric = $rubric->replicate();
                            $newRubric->task_id = $newTask->id;
                            $newRubric->save();
                        });
                    });
                });

            \Illuminate\Support\Facades\Log::info('TrainingProgram: cloned', [
                'event'           => 'program_cloned',
                'source_id'       => $this->id,
                'source_name'     => $this->name,
                'new_id'          => $newProgram->id,
                'new_name'        => $newProgram->name,
                'new_year'        => $year,
                'sections_cloned' => $this->sections()->count(),
            ]);

            return $newProgram;
        });
    }
}
