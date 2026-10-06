<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds two columns to the users table to support the interview-score feature:
     *
     * - interview_score: decimal (0–100), entered by the super-admin on the user
     *   record. Represents the candidate's interview performance as a percentage.
     *   Nullable — null means the interview has not been scored yet.
     *
     * - interview_score_published: boolean flag. When false the score is private
     *   (admin-only). When true it becomes visible to the student, reviewer, and
     *   observer panels. Saves immediately on toggle — no approval step.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('interview_score', 5, 2)
                ->nullable()
                ->after('disqualified_at')
                ->comment('Interview score as a percentage (0–100), entered by admin');

            $table->boolean('interview_score_published')
                ->default(false)
                ->after('interview_score')
                ->comment('When true, interview_score is visible to students/reviewers/observers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['interview_score', 'interview_score_published']);
        });
    }
};
