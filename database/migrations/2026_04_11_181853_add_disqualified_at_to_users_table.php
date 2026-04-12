<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Set by admin when a candidate is disqualified.
            // A disqualified candidate CANNOT log in at all (harder lock than graduation).
            // Can be reversed by admin if the candidate later meets requirements.
            $table->timestamp('disqualified_at')->nullable()->after('program_completed_at');

            // Optional: reason stored for audit trail
            $table->string('disqualification_reason')->nullable()->after('disqualified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['disqualified_at', 'disqualification_reason']);
        });
    }
};
