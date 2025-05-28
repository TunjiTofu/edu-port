<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReviewsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $submissions = Submission::all();
        $trainers = User::where('role_id', 2)->get();

        foreach ($submissions as $submission) {
            Review::create([
                'submission_id' => $submission->id,
                'reviewer_id' => $trainers->random()->id,
                'score' => rand(1, 10),
                'comments' => 'Good work, but could use more detail.',
                'is_completed' => true,
                'reviewed_at' => now(),
                'admin_override' => false,
                'override_reason' => null,
                'overridden_by' => null,
                'overridden_at' => null,
            ]);
        }
    }
}
