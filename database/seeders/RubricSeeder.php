<?php

namespace Database\Seeders;

use App\Models\Rubric;
use App\Models\Task;
use Illuminate\Database\Seeder;

class RubricSeeder extends Seeder
{
    /**
     * Default rubric criteria applied to every task.
     *
     * REFACTOR: Extracted to a public constant so that SyncTaskRubrics Artisan
     * command can reference the same source of truth. Previously this array was
     * duplicated verbatim in both this seeder and the console command — any
     * change to criteria had to be made in two places.
     *
     * Usage in SyncTaskRubrics.php:
     *   use Database\Seeders\RubricSeeder;
     *   $criteria = RubricSeeder::DEFAULT_CRITERIA;
     */
    public const DEFAULT_CRITERIA = [
        [
            'title'       => 'Content Quality',
            'description' => 'Accuracy, relevance, and depth of content provided',
            'max_points'  => 3.0,
            'order_index' => 1,
        ],
        [
            'title'       => 'Organization & Structure',
            'description' => 'Clear organization, logical flow, and proper structure',
            'max_points'  => 2.0,
            'order_index' => 2,
        ],
        [
            'title'       => 'Completeness',
            'description' => 'All required elements and components are included',
            'max_points'  => 2.0,
            'order_index' => 3,
        ],
        [
            'title'       => 'Presentation & Format',
            'description' => 'Professional presentation, proper formatting, and visual appeal',
            'max_points'  => 2.0,
            'order_index' => 4,
        ],
        [
            'title'       => 'Timeliness',
            'description' => 'Submitted on time and met all deadline requirements',
            'max_points'  => 1.0,
            'order_index' => 5,
        ],
    ];

    /**
     * Run the database seeds.
     *
     * NOTE: This seeder was previously called from inside the create_rubrics_table
     * migration via Artisan::call() — an anti-pattern that has been removed.
     * Run this seeder independently: php artisan db:seed --class=RubricSeeder
     *
     * FIX: Uses firstOrCreate on (task_id, title) to prevent duplicate rubrics
     * when the seeder is run more than once.
     */
    public function run(): void
    {
        $tasks = Task::all();

        if ($tasks->isEmpty()) {
            $this->command->warn('No tasks found. Skipping rubric seeding.');
            return;
        }

        foreach ($tasks as $task) {
            foreach (self::DEFAULT_CRITERIA as $rubricData) {
                Rubric::firstOrCreate(
                    [
                        'task_id' => $task->id,
                        'title'   => $rubricData['title'],
                    ],
                    [
                        'description' => $rubricData['description'],
                        'max_points'  => $rubricData['max_points'],
                        'order_index' => $rubricData['order_index'],
                        'is_active'   => true,
                    ]
                );
            }
        }

        // Task-specific rubric: only for tasks whose title contains 'research'
        $researchTask = Task::where('title', 'LIKE', '%research%')->first();

        if ($researchTask) {
            Rubric::firstOrCreate(
                [
                    'task_id' => $researchTask->id,
                    'title'   => 'Research Methodology',
                ],
                [
                    'description' => 'Demonstrates appropriate research methods and data collection techniques',
                    'max_points'  => 2.0,
                    'order_index' => 6,
                    'is_active'   => true,
                ]
            );
        }

        $this->command->info("Rubrics seeded for {$tasks->count()} tasks.");
    }
}
