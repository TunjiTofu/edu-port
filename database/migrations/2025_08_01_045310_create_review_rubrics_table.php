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
        Schema::create('review_rubrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->onDelete('cascade');
            $table->foreignId('rubric_id')->constrained()->onDelete('cascade');
            $table->decimal('points_awarded', 5, 2);
            $table->text('comments')->nullable();
            $table->boolean('is_checked')->default(false);
            $table->timestamps();

            $table->unique(['review_id', 'rubric_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_rubrics');
    }
};
