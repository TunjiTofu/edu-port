<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The checks table has a composite index on (submission_1_id, submission_2_id)
     * which covers forward lookups ("find all checks where submission A was
     * compared"). However reverse lookups ("find all checks involving submission B
     * as the second submission") require a full table scan without this index.
     *
     * Non-destructive: safe to run on existing databases with live 2025 data.
     */
    public function up(): void
    {
        Schema::table('checks', function (Blueprint $table) {
            $table->index('submission_2_id', 'idx_checks_submission_2_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checks', function (Blueprint $table) {
//            $table->dropIndex('idx_checks_submission_2_id');
        });
    }
};
