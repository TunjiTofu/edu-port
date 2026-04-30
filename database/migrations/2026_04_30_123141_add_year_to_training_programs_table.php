<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_programs', function (Blueprint $table) {
            // Additive only — existing records get backfilled below
            $table->unsignedSmallInteger('year')
                ->nullable()
                ->after('name')
                ->comment('Program cohort year e.g. 2025, 2026');
        });

        // Backfill all existing programs as 2025
        DB::table('training_programs')->update(['year' => 2025]);
    }

    public function down(): void
    {
        Schema::table('training_programs', function (Blueprint $table) {
            $table->dropColumn('year');
        });
    }
};
