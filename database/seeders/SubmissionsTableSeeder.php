<?php

namespace Database\Seeders;

use App\Models\Submission;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubmissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tasks = Task::all();
        $students = User::where('role_id', 4)->get();

        foreach ($tasks as $task) {
            foreach ($students->random(5) as $student) {
                Submission::create([
                    'task_id' => $task->id,
                    'student_id' => $student->id,
                    'file_name' => 'submission.pdf',
                    'file_path' => 'submissions/'.$task->id.'/'.$student->id,
                    'file_type' => 'application/pdf',
                    'file_size' => 1024,
                    'content_text' => 'Sample submission content text',
                    'status' => 'submitted',
                    'submitted_at' => now()
                ]);
            }
        }
    }
}
