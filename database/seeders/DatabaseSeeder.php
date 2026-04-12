<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Order matters — each seeder depends on the ones above it:
     *   Roles → Districts → Churches → Users
     *   → Training Programs → Sections → Tasks
     *   → Program Enrollments
     *
     * Submission/review/similarity seeders are commented out intentionally —
     * they generate fake file records that don't correspond to real uploaded
     * files, which can cause errors in the submission download flow.
     * Uncomment only when working with fake/test data on a local environment.
     *
     * NOTE: RubricSeeder is NOT called from the migration anymore (that was
     * an antipattern). It is registered here so it runs after Tasks are seeded.
     */
    public function run(): void
    {
        $this->call([
            RolesTableSeeder::class,
            DistrictTableSeeder::class,
            ChurchesTableSeeder::class,
            UsersTableSeeder::class,
            TrainingProgramTableSeeder::class,
            SectionsTableSeeder::class,
            TasksTableSeeder::class,
            RubricSeeder::class,
            ProgramEnrollmentsTableSeeder::class,

            // Uncomment below only for local/test environments with fake file data:
            // SubmissionsTableSeeder::class,
            // ReviewsTableSeeder::class,
            // SimilarityChecksTableSeeder::class,
            // ResultPublicationsTableSeeder::class,
        ]);
    }
}
