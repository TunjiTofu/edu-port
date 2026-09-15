<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            // Tracks whether this submission is a resubmission after a
            // needs_revision request. Used to:
            //   1. Show "Resubmission" badge in admin and reviewer panels
            //   2. Block the student from resubmitting again until the
            //      reviewer sends it back for revision a second time
            $table->boolean('is_resubmission')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn('is_resubmission');
        });
    }
};
