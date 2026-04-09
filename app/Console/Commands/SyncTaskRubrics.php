<?php

namespace App\Console\Commands;

use App\Models\Rubric;
use App\Models\Task;
use Database\Seeders\RubricSeeder;
use Illuminate\Console\Command;

class SyncTaskRubrics extends Command
{
    protected $signature = 'tasks:sync-rubrics {--task-id= : Specific task ID to sync}';
    protected $description = 'Sync rubrics for tasks that don\'t have any rubrics assigned';

    /**
     * Execute the console command.
     *
     * REFACTOR: Default criteria now pulled from RubricSeeder::DEFAULT_CRITERIA
     * instead of being duplicated here. Single source of truth — any change to
     * default rubric structure only needs to happen in RubricSeeder.
     *
     * FIX: The --task-id path now also guards against duplicate rubrics via
     * firstOrCreate, matching the seeder behaviour.
     */
    public function handle(): int
    {
        $taskId = $this->option('task-id');

        $tasks = $taskId
            ? Task::where('id', $taskId)->get()
            : Task::doesntHave('rubrics')->get();

        if ($tasks->isEmpty()) {
            $this->info('No tasks found that need rubric syncing.');
            return 0;
        }

        $progressBar = $this->output->createProgressBar($tasks->count());
        $progressBar->start();

        $created = 0;
        $skipped = 0;

        foreach ($tasks as $task) {
            foreach (RubricSeeder::DEFAULT_CRITERIA as $criteria) {
                $rubric = Rubric::firstOrCreate(
                    [
                        'task_id' => $task->id,
                        'title'   => $criteria['title'],
                    ],
                    [
                        'description' => $criteria['description'],
                        'max_points'  => $criteria['max_points'],
                        'order_index' => $criteria['order_index'],
                        'is_active'   => true,
                    ]
                );

                $rubric->wasRecentlyCreated ? $created++ : $skipped++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("Done. Created: {$created} rubrics | Already existed: {$skipped}.");

        return 0;
    }
}
