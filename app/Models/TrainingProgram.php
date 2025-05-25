<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingProgram extends Model
{
    use HasFactory;

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
    public function sections(): HasMany {
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


}
