<?php

use App\Enums\ProgramEnrollmentStatus;
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
        Schema::create('program_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('training_program_id')->constrained()->onDelete('cascade');
            $table->timestamp('enrolled_at');
            $table->string('status')->default(ProgramEnrollmentStatus::ACTIVE->value);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['student_id', 'training_program_id'], 'unique_student_program_enrollment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_enrollments');
    }
};
