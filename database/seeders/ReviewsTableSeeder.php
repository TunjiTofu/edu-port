<?php

namespace Database\Seeders;

use App\Enums\RoleTypes;
use App\Models\Review;
use App\Models\Role;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * FIXES:
     * 1. Reviewer role looked up by enum value — original used hardcoded role_id: 2.
     * 2. Guards against missing reviewers.
     * 3. Uses firstOrCreate on submission_id to avoid duplicate reviews.
     * 4. Scores now use floats consistent with decimal(4,1) column type.
     */
    public function run(): void
    {
        $reviewerRole = Role::where('name', RoleTypes::REVIEWER->value)->first();

        if (! $reviewerRole) {
            $this->command->warn('Reviewer role not found. Skipping review seeding.');
            return;
        }

        $reviewers = User::where('role_id', $reviewerRole->id)
            ->where('is_active', true)
            ->get();

        if ($reviewers->isEmpty()) {
            $this->command->warn('No active reviewers found. Skipping review seeding.');
            return;
        }

        $submissions = Submission::all();

        if ($submissions->isEmpty()) {
            $this->command->warn('No submissions found. Skipping review seeding.');
            return;
        }

        foreach ($submissions as $submission) {
            Review::firstOrCreate(
                ['submission_id' => $submission->id],
                [
                    'reviewer_id'   => $reviewers->random()->id,
                    'score'         => round(mt_rand(10, 100) / 10, 1), // 1.0 – 10.0
                    'comments'      => 'Good work, but could use more detail.',
                    'is_completed'  => true,
                    'reviewed_at'   => now(),
                    'admin_override'=> false,
                    'override_reason' => null,
                    'overridden_by' => null,
                    'overridden_at' => null,
                ]
            );
        }

        $this->command->info("Seeded reviews for {$submissions->count()} submissions.");
    }
}
