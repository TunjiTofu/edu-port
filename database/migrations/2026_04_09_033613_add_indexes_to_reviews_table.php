<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds performance indexes to the reviews table that were absent
     * from the original migration. These are critical for:
     * - reviewer_id: every reviewer dashboard filters by this column
     * - submission_id: joining reviews to submissions (FK was defined
     *   but explicit index helps query planner on large datasets)
     * - is_completed: filtering pending vs completed reviews
     * - (reviewer_id, is_completed): most common reviewer query pattern
     *
     * Non-destructive: safe to run on existing databases with live 2025 data.
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->index('reviewer_id', 'idx_reviews_reviewer_id');
            $table->index('is_completed', 'idx_reviews_is_completed');
            $table->index(['reviewer_id', 'is_completed'], 'idx_reviews_reviewer_completed');
            $table->index('reviewed_at', 'idx_reviews_reviewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
//            $table->dropIndex('idx_reviews_reviewer_id');
//            $table->dropIndex('idx_reviews_is_completed');
//            $table->dropIndex('idx_reviews_reviewer_completed');
//            $table->dropIndex('idx_reviews_reviewed_at');
        });
    }
};
