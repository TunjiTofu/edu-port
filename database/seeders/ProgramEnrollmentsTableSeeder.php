<?php

namespace Database\Seeders;

use App\Enums\ProgramEnrollmentStatus;
use App\Models\ProgramEnrollment;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\Utility\Constants;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProgramEnrollmentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = User::where('role_id', Constants::STUDENT_ID)->get();
        $programs = TrainingProgram::all();

        foreach ($students as $student) {
            ProgramEnrollment::create([
                'student_id' => $student->id,
                'training_program_id' => $programs->random()->id,
                'enrolled_at' => now(),
                'status' => ProgramEnrollmentStatus::ACTIVE->value
            ]);
        }
    }
}
