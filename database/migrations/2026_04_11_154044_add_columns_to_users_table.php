<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // MG Mentor — free text, full name of the candidate's assigned mentor
            $table->string('mg_mentor')->nullable()->after('passport_photo');

            // Set by admin when marking candidate as having completed the program.
            // Non-null = graduated / completed. Used by EnsureProgramNotCompleted
            // middleware and canSubmit() checks across the student panel.
            $table->timestamp('program_completed_at')->nullable()->after('mg_mentor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['mg_mentor', 'program_completed_at']);
        });
    }
};
