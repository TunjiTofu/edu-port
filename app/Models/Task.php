<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];
    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'due_date' => 'datetime',
        ];
    }

    /**
     * Get the section that owns the task.
     *
     * @return BelongsTo
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the submissions for the task.
     *
     * @return HasMany
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class)->orderBy('submitted_at', 'desc');
    }

    public function rubrics(): HasMany
    {
        return $this->hasMany(Rubric::class)->where('is_active', true)->orderBy('order_index');
    }

    /**
     * result publication
     *
     * @return HasOne
     */
    public function resultPublication(): HasOne
    {
        return $this->hasOne(ResultPublication::class);
    }

    public function resultPublications(): HasMany
    {
        return $this->hasMany(ResultPublication::class);
    }

    /**
     * check if results are published for the task
     *
     * @return boolean
     */
    public function isResultsPublished(): bool
    {
        return $this->resultPublications()->where('is_published', true)->exists();
    }

    public function getPublishedResult()
    {
        return $this->resultPublications()->where('is_published', true)->first();
    }

    public function getTotalRubricPoints(): float
    {
        return $this->rubrics()->sum('max_points');
    }
}
