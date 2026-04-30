<?php

namespace App\Console\Commands;

use App\Models\Rubric;
use App\Models\Section;
use App\Models\Task;
use App\Models\TrainingProgram;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CloneProgram extends Command
{
    protected $signature = 'program:clone
                            {source : ID of the program to clone}
                            {name   : Name for the new program}
                            {year   : Year for the new program (e.g. 2026)}';

    protected $description = 'Clone a training program — copies all sections, tasks, and rubrics into a new program record.';

    public function handle(): int
    {
        $source = TrainingProgram::find($this->argument('source'));

        if (! $source) {
            $this->error("Training program with ID {$this->argument('source')} not found.");
            return self::FAILURE;
        }

        $newName = $this->argument('name');
        $newYear = (int) $this->argument('year');

        $this->info("Cloning '{$source->name}' → '{$newName}' ({$newYear})");
        $this->info("Source has {$source->sections()->count()} sections.");

        $confirm = $this->confirm("This will create a new program and duplicate all sections, tasks, and rubrics. Continue?", true);
        if (! $confirm) {
            $this->warn("Aborted.");
            return self::SUCCESS;
        }

        DB::transaction(function () use ($source, $newName, $newYear) {

            // ── Clone the training program ────────────────────────────────
            $newProgram = $source->replicate(['name', 'year', 'is_active']);
            $newProgram->name      = $newName;
            $newProgram->year      = $newYear;
            $newProgram->is_active = false; // start inactive — admin activates when ready
            $newProgram->save();

            $this->info("✓ Created program: #{$newProgram->id} '{$newProgram->name}'");

            // ── Clone sections ────────────────────────────────────────────
            $sections = $source->sections()->with('tasks.rubrics')->get();

            foreach ($sections as $section) {
                $newSection = $section->replicate(['training_program_id']);
                $newSection->training_program_id = $newProgram->id;
                $newSection->save();

                // ── Clone tasks ───────────────────────────────────────────
                foreach ($section->tasks as $task) {
                    $newTask = $task->replicate(['section_id']);
                    $newTask->section_id = $newSection->id;
                    $newTask->save();

                    // ── Clone rubrics ─────────────────────────────────────
                    foreach ($task->rubrics as $rubric) {
                        $newRubric = $rubric->replicate(['task_id']);
                        $newRubric->task_id = $newTask->id;
                        $newRubric->save();
                    }

                    $this->line("  ✓ Task '{$newTask->title}' ({$task->rubrics->count()} rubrics)");
                }

                $this->info("✓ Section '{$newSection->name}' ({$section->tasks->count()} tasks)");
            }

            $this->newLine();
            $this->info("✅ Done! New program ID: {$newProgram->id}");
            $this->warn("The new program is INACTIVE. Go to Admin → Training Programs and activate it when ready.");
        });

        return self::SUCCESS;
    }
}
