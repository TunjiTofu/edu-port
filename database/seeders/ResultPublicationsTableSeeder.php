<?php

namespace Database\Seeders;

use App\Enums\RoleTypes;
use App\Models\ResultPublication;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class ResultPublicationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * FIXES:
     * 1. Admin role looked up by enum value — original used hardcoded role_id: 2
     *    (which is actually the Reviewer role, not Admin).
     * 2. Fixed contradictory state: original used rand(0, 1) for is_published but
     *    always set published_at to now() — even for unpublished records.
     *    Now: unpublished records have null published_at and null published_by.
     * 3. Uses firstOrCreate on task_id to avoid duplicate publication records.
     * 4. Guards against missing admin users.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', RoleTypes::ADMIN->value)->first();

        if (! $adminRole) {
            $this->command->warn('Admin role not found. Skipping result publication seeding.');
            return;
        }

        $admin = User::where('role_id', $adminRole->id)
            ->where('is_active', true)
            ->first();

        if (! $admin) {
            $this->command->warn('No active admin user found. Skipping result publication seeding.');
            return;
        }

        $tasks = Task::all();

        if ($tasks->isEmpty()) {
            $this->command->warn('No tasks found. Skipping result publication seeding.');
            return;
        }

        foreach ($tasks as $task) {
            $isPublished = (bool) random_int(0, 1);

            ResultPublication::firstOrCreate(
                ['task_id' => $task->id],
                [
                    'is_published' => $isPublished,
                    'published_at' => $isPublished ? now() : null,
                    'published_by' => $isPublished ? $admin->id : null,
                ]
            );
        }

        $this->command->info("Seeded result publications for {$tasks->count()} tasks.");
    }
}
