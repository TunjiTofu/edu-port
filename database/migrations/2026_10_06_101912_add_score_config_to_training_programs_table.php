<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds per-programme scoring configuration columns:
     *
     * portfolio_weight  — percentage weight given to portfolio (default 60).
     * interview_weight  — percentage weight given to interview  (default 40).
     *   These two should always sum to 100; enforced by the form, not the DB.
     *
     * portfolio_cutoff  — minimum portfolio percentage a candidate must achieve
     *   to be considered qualified for interview (e.g. 60 means ≥60%).
     *   NULL = no cutoff configured; system shows the score but no pass/fail.
     *
     * total_cutoff      — minimum combined score (portfolio weighted +
     *   interview weighted, out of 100) a candidate must achieve to qualify for
     *   investiture (e.g. 50 means ≥50/100).
     *   NULL = no cutoff configured.
     */
    public function up(): void
    {
        Schema::table('training_programs', function (Blueprint $table) {
            $table->decimal('portfolio_weight', 5, 2)
                ->default(60)
                ->after('passing_score')
                ->comment('Portfolio percentage weight (default 60)');

            $table->decimal('interview_weight', 5, 2)
                ->default(40)
                ->after('portfolio_weight')
                ->comment('Interview percentage weight (default 40)');

            $table->decimal('portfolio_cutoff', 5, 2)
                ->nullable()
                ->after('interview_weight')
                ->comment('Portfolio % cutoff to qualify for interview; null = not set');

            $table->decimal('total_cutoff', 5, 2)
                ->nullable()
                ->after('portfolio_cutoff')
                ->comment('Combined score % cutoff to qualify for investiture; null = not set');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_programs', function (Blueprint $table) {
            $table->dropColumn([
                'portfolio_weight',
                'interview_weight',
                'portfolio_cutoff',
                'total_cutoff',
            ]);
        });
    }
};
