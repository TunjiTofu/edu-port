<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
            'end_date' => 'datetime',
            'is_active' => 'boolean',
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
}
