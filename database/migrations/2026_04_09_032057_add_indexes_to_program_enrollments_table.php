<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The original add_indexes_to_program_enrollments_table migration had its
     * indexes placed in down() instead of up(), so they were never applied to
     * existing databases. This migration safely adds them to any database where
     * they are still missing, using DB::statement with conditional logic to
     * avoid duplicate-index errors.
     */

    public function up(): void
    {
        Schema::table('program_enrollments', function (Blueprint $table) {

            if (!Schema::hasIndex('program_enrollments', 'idx_student_id_status')) {
                $table->index(['student_id', 'status'], 'idx_student_id_status');
            }

            if (!Schema::hasIndex('program_enrollments', 'idx_training_program_id_status')) {
                $table->index(['training_program_id', 'status'], 'idx_training_program_id_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('program_enrollments', function (Blueprint $table) {

            if (Schema::hasIndex('program_enrollments', 'idx_student_id_status')) {
                $table->dropIndex('idx_student_id_status');
            }

//            if (Schema::hasIndex('program_enrollments', 'idx_training_program_id_status')) {
//                $table->dropIndex('idx_training_program_id_status');
//            }
        });
    }
};
