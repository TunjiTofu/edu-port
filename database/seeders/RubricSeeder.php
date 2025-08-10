<?php

namespace Database\Seeders;

use App\Models\Rubric;
use App\Models\Task;
use Illuminate\Database\Seeder;

class RubricSeeder extends Seeder
{
    public function run(): void
    {
        // Example rubrics for different task types
        $tasks = Task::all();

        foreach ($tasks as $task) {
            // Generic rubrics that can be applied to most tasks
            $rubrics = [
                [
                    'title' => 'Content Quality',
                    'description' => 'Accuracy, relevance, and depth of content provided',
                    'max_points' => 3.0,
                    'order_index' => 1,
                ],
                [
                    'title' => 'Organization & Structure',
                    'description' => 'Clear organization, logical flow, and proper structure',
                    'max_points' => 2.0,
                    'order_index' => 2,
                ],
                [
                    'title' => 'Completeness',
                    'description' => 'All required elements and components are included',
                    'max_points' => 2.0,
                    'order_index' => 3,
                ],
                [
                    'title' => 'Presentation & Format',
                    'description' => 'Professional presentation, proper formatting, and visual appeal',
                    'max_points' => 2.0,
                    'order_index' => 4,
                ],
                [
                    'title' => 'Timeliness',
                    'description' => 'Submitted on time and met all deadline requirements',
                    'max_points' => 1.0,
                    'order_index' => 5,
                ],
            ];

            foreach ($rubrics as $rubricData) {
                Rubric::create([
                    'task_id' => $task->id,
                    'title' => $rubricData['title'],
                    'description' => $rubricData['description'],
                    'max_points' => $rubricData['max_points'],
                    'order_index' => $rubricData['order_index'],
                    'is_active' => true,
                ]);
            }
        }

        // You can also create task-specific rubrics
        $specificTask = Task::where('title', 'LIKE', '%research%')->first();
        if ($specificTask) {
            Rubric::create([
                'task_id' => $specificTask->id,
                'title' => 'Research Methodology',
                'description' => 'Demonstrates appropriate research methods and data collection techniques',
                'max_points' => 2.0,
                'order_index' => 6,
                'is_active' => true,
            ]);
        }
    }
}
