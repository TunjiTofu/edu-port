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
        Schema::create('checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_1_id')->constrained('submissions')->onDelete('cascade');
            $table->foreignId('submission_2_id')->constrained('submissions')->onDelete('cascade');
            $table->decimal('similarity_percentage', 5, 2);
            $table->json('matched_segments')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['submission_1_id', 'submission_2_id'], 'checks_submission_pair_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checks');
    }
};
