<?php

namespace Database\Seeders;

use App\Models\ResultPublication;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ResultPublicationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tasks = Task::all();

        foreach ($tasks as $task) {
            ResultPublication::create([
                'task_id' => $task->id,
                'is_published' => rand(0, 1),
                'published_at' => now(),
                'published_by' => User::where('role_id', 2)->first()->id
            ]);
        }
    }
}
