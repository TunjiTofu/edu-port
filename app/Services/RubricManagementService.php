<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Rubric;
use App\Models\Review;
use App\Models\ReviewRubric;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RubricManagementService
{
    /**
     * Sync all review rubrics for a task when rubrics are updated
     */
    public function syncTaskRubrics(Task $task): array
    {
        $results = [
            'synced_reviews' => 0,
            'created_rubrics' => 0,
            'updated_rubrics' => 0,
            'errors' => []
        ];

        try {
            DB::beginTransaction();

            // Get all reviews for this task
            $reviews = Review::whereHas('submission', function ($query) use ($task) {
                $query->where('task_id', $task->id);
            })->get();

            foreach ($reviews as $review) {
                $syncResult = $this->syncReviewRubrics($review);
                $results['synced_reviews']++;
                $results['created_rubrics'] += $syncResult['created'];
                $results['updated_rubrics'] += $syncResult['updated'];
            }

            DB::commit();

            Log::info('Task rubrics synchronized', [
                'task_id' => $task->id,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = $e->getMessage();

            Log::error('Failed to sync task rubrics', [
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);
        }

        return $results;
    }

    /**
     * Sync rubrics for a specific review
     */
    public function syncReviewRubrics(Review $review): array
    {
        $results = ['created' => 0, 'updated' => 0];

        $taskRubrics = $review->submission->task->rubrics()->active()->get();

        foreach ($taskRubrics as $taskRubric) {
            $reviewRubric = ReviewRubric::firstOrCreate(
                [
                    'review_id' => $review->id,
                    'rubric_id' => $taskRubric->id,
                ],
                [
                    'is_checked' => false,
                    'points_awarded' => 0,
                    'comments' => null,
                ]
            );

            if ($reviewRubric->wasRecentlyCreated) {
                $results['created']++;
            }
        }

        return $results;
    }

    /**
     * Calculate rubric statistics for a task
     */
    public function getTaskRubricStatistics(Task $task): array
    {
        $submissions = $task->submissions()->with(['reviews.reviewRubrics'])->get();

        $stats = [
            'total_submissions' => $submissions->count(),
            'reviewed_submissions' => 0,
            'average_score' => 0,
            'average_rubric_score' => 0,
            'score_distribution' => [],
            'rubric_performance' => [],
            'completion_rate' => 0,
        ];

        if ($submissions->isEmpty()) {
            return $stats;
        }

        $reviewedSubmissions = $submissions->filter(function ($submission) {
            return $submission->reviews->isNotEmpty() && $submission->reviews->first()->is_completed;
        });

        $stats['reviewed_submissions'] = $reviewedSubmissions->count();
        $stats['completion_rate'] = $stats['total_submissions'] > 0
            ? ($stats['reviewed_submissions'] / $stats['total_submissions']) * 100
            : 0;

        if ($reviewedSubmissions->isEmpty()) {
            return $stats;
        }

        // Calculate average scores
        $totalScore = 0;
        $totalRubricScore = 0;
        $scoreDistribution = [];

        foreach ($reviewedSubmissions as $submission) {
            $review = $submission->reviews->first();
            $score = $review->score ?? 0;
            $rubricScore = $review->getTotalRubricScore();

            $totalScore += $score;
            $totalRubricScore += $rubricScore;

            // Score distribution (rounded to nearest 10%)
            $percentage = $task->max_score > 0 ? ($score / $task->max_score) * 100 : 0;
            $bucket = floor($percentage / 10) * 10;
            $scoreDistribution[$bucket] = ($scoreDistribution[$bucket] ?? 0) + 1;
        }

        $stats['average_score'] = $stats['reviewed_submissions'] > 0
            ? round($totalScore / $stats['reviewed_submissions'], 2)
            : 0;

        $stats['average_rubric_score'] = $stats['reviewed_submissions'] > 0
            ? round($totalRubricScore / $stats['reviewed_submissions'], 2)
            : 0;

        $stats['score_distribution'] = $scoreDistribution;

        // Rubric performance analysis
        $rubrics = $task->rubrics()->active()->get();
        foreach ($rubrics as $rubric) {
            $rubricStats = $this->getRubricPerformanceStats($rubric, $reviewedSubmissions);
            $stats['rubric_performance'][$rubric->id] = $rubricStats;
        }

        return $stats;
    }

    /**
     * Get performance statistics for a specific rubric
     */
    private function getRubricPerformanceStats(Rubric $rubric, Collection $submissions): array
    {
        $stats = [
            'rubric_title' => $rubric->title,
            'max_points' => $rubric->max_points,
            'times_checked' => 0,
            'average_points' => 0,
            'success_rate' => 0,
            'total_evaluations' => 0,
        ];

        $totalPoints = 0;
        $evaluationCount = 0;
        $checkedCount = 0;

        foreach ($submissions as $submission) {
            $review = $submission->reviews->first();
            if (!$review) continue;

            $reviewRubric = $review->reviewRubrics()
                ->where('rubric_id', $rubric->id)
                ->first();

            if ($reviewRubric) {
                $evaluationCount++;
                $totalPoints += $reviewRubric->points_awarded;

                if ($reviewRubric->is_checked) {
                    $checkedCount++;
                }
            }
        }

        $stats['total_evaluations'] = $evaluationCount;
        $stats['times_checked'] = $checkedCount;
        $stats['success_rate'] = $evaluationCount > 0 ? ($checkedCount / $evaluationCount) * 100 : 0;
        $stats['average_points'] = $evaluationCount > 0 ? round($totalPoints / $evaluationCount, 2) : 0;

        return $stats;
    }

    /**
     * Validate rubric consistency across a task
     */
    public function validateTaskRubrics(Task $task): array
    {
        $issues = [];

        // Check if rubric total matches task max score
        $rubricTotal = $task->rubrics()->active()->sum('max_points');
        if (abs($rubricTotal - $task->max_score) > 0.01) {
            $issues[] = [
                'type' => 'score_mismatch',
                'message' => "Rubric total ({$rubricTotal}) doesn't match task max score ({$task->max_score})",
                'severity' => 'warning'
            ];
        }

        // Check for duplicate rubric titles
        $rubricTitles = $task->rubrics()->active()->pluck('title')->toArray();
        $duplicates = array_diff_assoc($rubricTitles, array_unique($rubricTitles));
        if (!empty($duplicates)) {
            $issues[] = [
                'type' => 'duplicate_titles',
                'message' => 'Duplicate rubric titles found: ' . implode(', ', $duplicates),
                'severity' => 'warning'
            ];
        }

        // Check for missing descriptions
        $missingDescriptions = $task->rubrics()->active()
            ->whereNull('description')
            ->orWhere('description', '')
            ->count();

        if ($missingDescriptions > 0) {
            $issues[] = [
                'type' => 'missing_descriptions',
                'message' => "{$missingDescriptions} rubrics are missing descriptions",
                'severity' => 'info'
            ];
        }

        // Check rubric order
        $orderIndices = $task->rubrics()->active()->pluck('order_index')->toArray();
        $expectedOrder = range(1, count($orderIndices));
        sort($orderIndices);

        if ($orderIndices !== $expectedOrder) {
            $issues[] = [
                'type' => 'order_issues',
                'message' => 'Rubric order indices are not sequential',
                'severity' => 'info'
            ];
        }

        return $issues;
    }

    /**
     * Auto-fix common rubric issues
     */
    public function autoFixRubricIssues(Task $task): array
    {
        $fixes = [];

        try {
            DB::beginTransaction();

            // Fix order indices
            $rubrics = $task->rubrics()->active()->orderBy('order_index')->get();
            foreach ($rubrics as $index => $rubric) {
                $newOrder = $index + 1;
                if ($rubric->order_index != $newOrder) {
                    $rubric->update(['order_index' => $newOrder]);
                    $fixes[] = "Updated order index for '{$rubric->title}' to {$newOrder}";
                }
            }

            // Adjust points to match task max score
            $rubricTotal = $task->rubrics()->active()->sum('max_points');
            $difference = $task->max_score - $rubricTotal;

            if (abs($difference) > 0.01 && $rubrics->isNotEmpty()) {
                $firstRubric = $rubrics->first();
                $newPoints = $firstRubric->max_points + $difference;

                if ($newPoints > 0) {
                    $firstRubric->update(['max_points' => round($newPoints, 2)]);
                    $fixes[] = "Adjusted '{$firstRubric->title}' points by {$difference} to match task total";
                }
            }

            DB::commit();

            Log::info('Auto-fixed rubric issues', [
                'task_id' => $task->id,
                'fixes' => $fixes
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to auto-fix rubric issues', [
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);
        }

        return $fixes;
    }

    /**
     * Generate rubric performance report
     */
    public function generateRubricReport(Task $task): array
    {
        $stats = $this->getTaskRubricStatistics($task);
        $issues = $this->validateTaskRubrics($task);

        return [
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'max_score' => $task->max_score,
                'rubric_count' => $task->rubrics()->active()->count(),
            ],
            'statistics' => $stats,
            'validation_issues' => $issues,
            'recommendations' => $this->generateRecommendations($stats, $issues),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Generate recommendations based on statistics and issues
     */
    private function generateRecommendations(array $stats, array $issues): array
    {
        $recommendations = [];

        // Low completion rate
        if ($stats['completion_rate'] < 50) {
            $recommendations[] = [
                'type' => 'completion',
                'message' => 'Low review completion rate. Consider reviewing assignment deadlines or rubric complexity.',
                'priority' => 'high'
            ];
        }

        // Score consistency issues
        $scoreDifference = abs($stats['average_score'] - $stats['average_rubric_score']);
        if ($scoreDifference > ($stats['average_score'] * 0.1)) { // More than 10% difference
            $recommendations[] = [
                'type' => 'consistency',
                'message' => 'Significant difference between manual and rubric scores. Consider rubric training for reviewers.',
                'priority' => 'medium'
            ];
        }

        // Rubric performance issues
        foreach ($stats['rubric_performance'] as $rubricStats) {
            if ($rubricStats['success_rate'] < 30) {
                $recommendations[] = [
                    'type' => 'rubric_difficulty',
                    'message' => "Rubric '{$rubricStats['rubric_title']}' has low success rate ({$rubricStats['success_rate']}%). Consider revising criteria or providing more guidance.",
                    'priority' => 'medium'
                ];
            }
        }

        // Validation issues
        foreach ($issues as $issue) {
            if ($issue['severity'] === 'warning') {
                $recommendations[] = [
                    'type' => 'validation',
                    'message' => $issue['message'],
                    'priority' => 'low'
                ];
            }
        }

        return $recommendations;
    }
}
