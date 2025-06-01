<?php

namespace Database\Seeders;

use App\Models\SimilarityCheck;
use App\Models\Submission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SimilarityChecksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $submissions = Submission::all();

        // Check first 10 submissions against each other
        $targetSubmissions = $submissions->take(10);

        foreach ($targetSubmissions as $i => $sub1) {
            foreach ($targetSubmissions as $j => $sub2) {
                if ($i < $j && $sub1->task_id === $sub2->task_id) {
                    SimilarityCheck::create([
                        'submission_1_id' => $sub1->id,
                        'submission_2_id' => $sub2->id,
                        'similarity_percentage' => rand(0, 10000) / 100,
                        'matched_segments' => [
                            ['start' => 0, 'end' => 100, 'text' => 'common text segment']
                        ]
                    ]);
                }
            }
        }
    }
}
