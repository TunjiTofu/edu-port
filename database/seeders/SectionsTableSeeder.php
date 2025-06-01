<?php

namespace Database\Seeders;

use App\Models\Section;
use App\Models\TrainingProgram;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SectionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $programs = TrainingProgram::all();

        foreach ($programs as $program) {
            Section::create([
                'name' => 'Introduction',
                'description' => 'Program introduction and overview',
                'training_program_id' => $program->id,
                'order_index' => 1
            ]);

            Section::create([
                'name' => 'Core Concepts',
                'description' => 'Fundamental concepts and theories',
                'training_program_id' => $program->id,
                'order_index' => 2
            ]);

            Section::create([
                'name' => 'Practical Application',
                'description' => 'Hands-on practice and implementation',
                'training_program_id' => $program->id,
                'order_index' => 3
            ]);
        }
    }
}
