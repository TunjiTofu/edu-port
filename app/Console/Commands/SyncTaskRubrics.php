<?php

namespace App\Console\Commands;

use App\Models\Rubric;
use App\Models\Task;
use Illuminate\Console\Command;

class SyncTaskRubrics extends Command
{
    protected $signature = 'tasks:sync-rubrics {--task-id= : Specific task ID to sync}';
    protected $description = 'Sync rubrics for tasks that don\'t have any rubrics assigned';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $taskId = $this->option('task-id');

        if ($taskId) {
            $tasks = Task::where('id', $taskId)->get();
        } else {
            $tasks = Task::doesntHave('rubrics')->get();
        }

        if ($tasks->isEmpty()) {
            $this->info('No tasks found that need rubric syncing.');
            return 0;
        }

        $defaultCriteria = [
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

        $progressBar = $this->output->createProgressBar($tasks->count());
        $progressBar->start();

        foreach ($tasks as $task) {
            foreach ($defaultCriteria as $criteria) {
                Rubric::create([
                    'task_id' => $task->id,
                    'title' => $criteria['title'],
                    'description' => $criteria['description'],
                    'max_points' => $criteria['max_points'],
                    'order_index' => $criteria['order_index'],
                    'is_active' => true,
                ]);
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("Successfully synced rubrics for {$tasks->count()} tasks.");

        return 0;
    }
}
