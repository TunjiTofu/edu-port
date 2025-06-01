<?php

namespace Database\Seeders;

use App\Models\Section;
use App\Models\Task;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TasksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sections = Section::all();

        foreach ($sections as $section) {
            Task::create([
                'title' => 'Initial Assessment',
                'description' => 'Initial knowledge check',
                'instructions' => 'Complete the assessment questions',
                'section_id' => $section->id,
                'max_score' => 8.5,
                'due_date' => now()->addWeeks(2),
                'order_index' => 1
            ]);

            Task::create([
                'title' => 'Case Study Analysis',
                'description' => 'Real-world scenario analysis',
                'instructions' => 'Submit your analysis report',
                'section_id' => $section->id,
                'max_score' => 6,
                'due_date' => now()->addWeeks(4),
                'order_index' => 2
            ]);
        }
    }
}
