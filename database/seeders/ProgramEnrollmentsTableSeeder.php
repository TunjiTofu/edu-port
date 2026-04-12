<?php

namespace Database\Seeders;

use App\Enums\ProgramEnrollmentStatus;
use App\Enums\RoleTypes;
use App\Models\ProgramEnrollment;
use App\Models\Role;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProgramEnrollmentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * FIXES:
     * 1. Guard against empty $programs collection — original called
     *    $programs->random() which throws on an empty collection.
     * 2. Role looked up by enum value instead of hardcoded Constants::STUDENT_ID.
     * 3. Uses firstOrCreate to prevent duplicate enrollment seeding.
     * 4. Skips students who have no active programs to enroll into.
     */
    public function run(): void
    {
        $programs = TrainingProgram::where('is_active', true)->get();

        if ($programs->isEmpty()) {
            $this->command->warn('No active training programs found. Skipping enrollment seeding.');
            return;
        }

        $studentRole = Role::where('name', RoleTypes::STUDENT->value)->first();

        if (! $studentRole) {
            $this->command->warn('Student role not found. Skipping enrollment seeding.');
            return;
        }

        $students = User::where('role_id', $studentRole->id)
            ->where('is_active', true)
            ->get();

        if ($students->isEmpty()) {
            $this->command->warn('No active students found. Skipping enrollment seeding.');
            return;
        }

        foreach ($students as $student) {
            // Pick a random active program for this student
            $program = $programs->random();

            ProgramEnrollment::firstOrCreate(
                [
                    'student_id'         => $student->id,
                    'training_program_id' => $program->id,
                ],
                [
                    'enrolled_at' => now(),
                    'status'      => ProgramEnrollmentStatus::ACTIVE->value,
                ]
            );
        }

        $this->command->info("Seeded enrollments for {$students->count()} students.");
    }
}
