<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimilarityCheck extends Model
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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'similarity_percentage' => 'decimal:2',
            'matched_segments' => 'array'
        ];
    }

    /**
     * Submission 1 relationship.
     *
     * @return BelongsTo
     */
    public function submission1(): BelongsTo
    {
        return $this->belongsTo(Submission::class, 'submission_1_id');
    }

    /**
     * Submission 2 relationship.
     *
     * @return BelongsTo
     */
    public function submission2(): BelongsTo
    {
        return $this->belongsTo(Submission::class, 'submission_2_id');
    }
    
}
