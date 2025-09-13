<?php

namespace App\Services;

use App\Enums\SubmissionTypes;
use App\Models\Review;
use App\Models\Submission;
use App\Models\User;
use App\Services\Utility\Constants;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ReviewerAssignmentService
{
    public const STRATEGY_BALANCED = 'balanced';
    public const STRATEGY_ROUND_ROBIN = 'round_robin';
    public const STRATEGY_RANDOM = 'random';

    protected static int $roundRobinIndex = 0;

    public function __construct()
    {
        // Initialize round robin index based on current time to distribute load
        static::$roundRobinIndex = now()->minute % 10;
    }

    /**
     * Automatically assign reviewers to multiple submissions
     */
    public function assignReviewersToSubmissions(
        Collection $submissions,
        string $strategy = self::STRATEGY_BALANCED,
        bool $excludeSameChurch = true,
        bool $onlyActiveReviewers = true
    ): array {
        $results = [
            'assigned' => 0,
            'errors' => [],
            'assignments' => []
        ];

        // Filter submissions that need assignment
        $submissionsToAssign = $submissions->filter(function ($submission) {
            return $submission->status === SubmissionTypes::PENDING_REVIEW->value &&
                !$submission->review?->reviewer_id;
        });

        if ($submissionsToAssign->isEmpty()) {
            return $results;
        }

        // Get reviewer workloads
        $reviewerWorkloads = $this->getReviewerWorkloads($onlyActiveReviewers);

        if (empty($reviewerWorkloads)) {
            $results['errors'][] = 'No available reviewers found';
            return $results;
        }

        foreach ($submissionsToAssign as $submission) {
            try {
                $assignmentResult = $this->assignSingleSubmission(
                    $submission,
                    $reviewerWorkloads,
                    $strategy,
                    $excludeSameChurch
                );

                if ($assignmentResult['success']) {
                    $results['assigned']++;
                    $results['assignments'][] = [
                        'submission_id' => $submission->id,
                        'reviewer_id' => $assignmentResult['reviewer_id'],
                        'reviewer_name' => $assignmentResult['reviewer_name']
                    ];

                    // Update workload tracking
                    $reviewerWorkloads[$assignmentResult['reviewer_id']]['current_load']++;
                } else {
                    $results['errors'][] = $assignmentResult['error'];
                }

            } catch (\Exception $e) {
                $error = "Error assigning submission ID {$submission->id}: " . $e->getMessage();
                $results['errors'][] = $error;
                Log::error($error);
            }
        }

        return $results;
    }

    /**
     * Assign a single submission to a reviewer
     */
    protected function assignSingleSubmission(
        Submission $submission,
        array &$reviewerWorkloads,
        string $strategy,
        bool $excludeSameChurch
    ): array {
        // Filter eligible reviewers
        $eligibleReviewers = $this->getEligibleReviewers(
            $reviewerWorkloads,
            $submission,
            $excludeSameChurch
        );

        if (empty($eligibleReviewers)) {
            return [
                'success' => false,
                'error' => "No eligible reviewers for submission ID: {$submission->id}"
            ];
        }

        // Select reviewer based on strategy
        $selectedReviewer = $this->selectReviewerByStrategy($eligibleReviewers, $strategy);

        if (!$selectedReviewer) {
            return [
                'success' => false,
                'error' => "Could not select reviewer for submission ID: {$submission->id}"
            ];
        }

        // Create review record
        $review = $submission->review()->updateOrCreate(
            ['submission_id' => $submission->id],
            [
                'reviewer_id' => $selectedReviewer['reviewer']->id,
                'created_at' => now(),
            ]
        );

        // Update submission status
        $submission->update([
            'status' => SubmissionTypes::UNDER_REVIEW->value,
        ]);

        return [
            'success' => true,
            'reviewer_id' => $selectedReviewer['reviewer']->id,
            'reviewer_name' => $selectedReviewer['reviewer']->name
        ];
    }

    /**
     * Get current workload for all reviewers
     */
    public function getReviewerWorkloads(bool $onlyActive = true): array
    {
        $reviewersQuery = User::where('role_id', Constants::REVIEWER_ID);

        if ($onlyActive) {
            $reviewersQuery->where('is_active', true);
        }

        $reviewers = $reviewersQuery->get();
        $workloads = [];

        foreach ($reviewers as $reviewer) {
            $currentLoad = Review::where('reviewer_id', $reviewer->id)
                ->whereHas('submission', function ($query) {
                    $query->whereIn('status', [
                        SubmissionTypes::UNDER_REVIEW->value,
                        SubmissionTypes::PENDING_REVIEW->value
                    ]);
                })
                ->count();

            $completedCount = Review::where('reviewer_id', $reviewer->id)
                ->where('is_completed', true)
                ->count();

            $workloads[$reviewer->id] = [
                'reviewer' => $reviewer,
                'current_load' => $currentLoad,
                'completed_count' => $completedCount,
                'total_assigned' => $currentLoad + $completedCount,
                'workload_score' => $currentLoad * 2 + $completedCount // Weight pending reviews more
            ];
        }

        return $workloads;
    }

    /**
     * Filter reviewers based on eligibility rules
     */
    protected function getEligibleReviewers(
        array $reviewerWorkloads,
        Submission $submission,
        bool $excludeSameChurch
    ): array {
        return array_filter($reviewerWorkloads, function ($reviewerData) use ($submission, $excludeSameChurch) {
            $reviewer = $reviewerData['reviewer'];

            if ($excludeSameChurch && $submission->student) {
                return $reviewer->church_id !== $submission->student->church_id &&
                    $reviewer->district_id !== $submission->student->district_id;
            }

            return true;
        });
    }

    /**
     * Select reviewer based on assignment strategy
     */
    protected function selectReviewerByStrategy(array $eligibleReviewers, string $strategy): ?array
    {
        if (empty($eligibleReviewers)) {
            return null;
        }

        switch ($strategy) {
            case self::STRATEGY_BALANCED:
                return $this->selectByLowestLoad($eligibleReviewers);

            case self::STRATEGY_ROUND_ROBIN:
                return $this->selectByRoundRobin($eligibleReviewers);

            case self::STRATEGY_RANDOM:
                return $this->selectByRandom($eligibleReviewers);

            default:
                return $this->selectByLowestLoad($eligibleReviewers);
        }
    }

    /**
     * Select reviewer with lowest current workload
     */
    protected function selectByLowestLoad(array $eligibleReviewers): ?array
    {
        $minLoad = min(array_column($eligibleReviewers, 'current_load'));

        foreach ($eligibleReviewers as $reviewerData) {
            if ($reviewerData['current_load'] === $minLoad) {
                return $reviewerData;
            }
        }

        return null;
    }

    /**
     * Round-robin selection among reviewers with similar loads
     */
    protected function selectByRoundRobin(array $eligibleReviewers): ?array
    {
        // Sort by the current load first
        uasort($eligibleReviewers, function ($a, $b) {
            return $a['current_load'] <=> $b['current_load'];
        });

        $reviewersList = array_values($eligibleReviewers);

        // Find reviewers with a minimum load
        $minLoad = $reviewersList[0]['current_load'];
        $minLoadReviewers = array_filter($reviewersList, function ($reviewer) use ($minLoad) {
            return $reviewer['current_load'] === $minLoad;
        });

        // Round-robin among minimum load reviewers
        $selectedIndex = static::$roundRobinIndex % count($minLoadReviewers);
        static::$roundRobinIndex++;

        return array_values($minLoadReviewers)[$selectedIndex];
    }

    /**
     * Random selection weighted by an inverse load
     */
    protected function selectByRandom(array $eligibleReviewers): ?array
    {
        $minLoad = min(array_column($eligibleReviewers, 'current_load'));

        // Filter to reviewers with minimum load for fair random selection
        $minLoadReviewers = array_filter($eligibleReviewers, function ($reviewer) use ($minLoad) {
            return $reviewer['current_load'] === $minLoad;
        });

        $randomIndex = array_rand($minLoadReviewers);
        return $minLoadReviewers[$randomIndex];
    }

    /**
     * Get detailed workload statistics for all reviewers
     */
    public function getWorkloadStatistics(): array
    {
        $workloads = $this->getReviewerWorkloads();

        $stats = [
            'total_reviewers' => count($workloads),
            'active_reviewers' => 0,
            'total_pending' => 0,
            'total_completed' => 0,
            'average_load' => 0,
            'max_load' => 0,
            'min_load' => PHP_INT_MAX,
            'reviewers' => []
        ];

        foreach ($workloads as $reviewerId => $data) {
            if ($data['reviewer']->is_active) {
                $stats['active_reviewers']++;
            }

            $stats['total_pending'] += $data['current_load'];
            $stats['total_completed'] += $data['completed_count'];
            $stats['max_load'] = max($stats['max_load'], $data['current_load']);
            $stats['min_load'] = min($stats['min_load'], $data['current_load']);

            $stats['reviewers'][] = [
                'id' => $reviewerId,
                'name' => $data['reviewer']->name,
                'email' => $data['reviewer']->email,
                'is_active' => $data['reviewer']->is_active,
                'current_load' => $data['current_load'],
                'completed_count' => $data['completed_count'],
                'total_assigned' => $data['total_assigned'],
                'workload_score' => $data['workload_score'],
            ];
        }

        if ($stats['active_reviewers'] > 0) {
            $stats['average_load'] = round($stats['total_pending'] / $stats['active_reviewers'], 2);
        }

        if ($stats['min_load'] === PHP_INT_MAX) {
            $stats['min_load'] = 0;
        }

        // Sort reviewers by workload score
        usort($stats['reviewers'], function ($a, $b) {
            return $a['workload_score'] <=> $b['workload_score'];
        });

        return $stats;
    }
}
