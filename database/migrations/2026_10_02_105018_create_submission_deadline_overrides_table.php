<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_deadline_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade')
                ->comment('Admin who granted the override');
            $table->boolean('is_used')->default(false)
                ->comment('True once the student has used this override to submit');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            // One active override per student per task
            $table->unique(['student_id', 'task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_deadline_overrides');
    }
};
