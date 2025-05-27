<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
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
            ProgramEnrollmentsTableSeeder::class,
            SubmissionsTableSeeder::class,
            ReviewsTableSeeder::class,
            // SimilarityChecksTableSeeder::class,
            ResultPublicationsTableSeeder::class,
        ]);
    }
}
