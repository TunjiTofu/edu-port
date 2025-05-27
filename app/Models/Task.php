<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the submissions for the task.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function submissions()
    {
        return $this->hasMany(Submission::class)->orderBy('submitted_at', 'desc');
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

    /**
     * check if results are published for the task
     *
     * @return boolean
     */
    public function isResultsPublished(): bool
    {
        return $this->resultPublication?->is_published ?? false;
    }
}
