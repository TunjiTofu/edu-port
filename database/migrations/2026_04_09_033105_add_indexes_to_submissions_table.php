<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds performance indexes to the submissions table that were absent
     * from the original migration. These are critical for:
     * - student_id: every student dashboard query filters by student
     * - status: reviewer/admin dashboards filter by submission status
     * - (student_id, status): combined for student-scoped status queries
     * - task_id + student_id: uniqueness-style lookups in widget queries
     *
     * Non-destructive: safe to run on existing databases with live 2025 data.
     */
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            if (!Schema::hasIndex('submissions', 'idx_submissions_student_id')) {
                $table->index('student_id', 'idx_submissions_student_id');
            }

            if (!Schema::hasIndex('submissions', 'idx_submissions_status')) {
                $table->index('status', 'idx_submissions_status');
            }

            if (!Schema::hasIndex('submissions', 'idx_submissions_student_status')) {
                $table->index(['student_id', 'status'], 'idx_submissions_student_status');
            }

            if (!Schema::hasIndex('submissions', 'idx_submissions_task_student')) {
                $table->index(['task_id', 'student_id'], 'idx_submissions_task_student');
            }

            if (!Schema::hasIndex('submissions', 'idx_submissions_submitted_at')) {
                $table->index('submitted_at', 'idx_submissions_submitted_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
//            $table->dropIndex('idx_submissions_student_id');
//            $table->dropIndex('idx_submissions_status');
//            $table->dropIndex('idx_submissions_student_status');
//            $table->dropIndex('idx_submissions_task_student');
//            $table->dropIndex('idx_submissions_submitted_at');
        });
    }
};
