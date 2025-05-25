<?php

namespace Database\Seeders;

use App\Models\TrainingProgram;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TrainingProgramTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TrainingProgram::create([
            'name' => 'Leadership Development',
            'code' => 'LD-2024',
            'description' => 'Advanced leadership training program',
            'start_date' => now()->addDays(7),
            'end_date' => now()->addMonths(3),
            'is_active' => true
        ]);

        TrainingProgram::create([
            'name' => 'Technical Skills Bootcamp',
            'code' => 'TSB-2024',
            'description' => 'Intensive technical training program',
            'start_date' => now()->addDays(14),
            'end_date' => now()->addMonths(2),
            'is_active' => true
        ]);
    }
}
