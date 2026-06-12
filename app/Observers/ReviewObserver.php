<?php

namespace App\Observers;

use App\Mail\ReviewerAssignedMail;
use App\Mail\ReviewerUnassignedMail;
use App\Models\Review;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ReviewObserver
{
    /**
     * Fired when a new Review record is created — this is how a submission
     * gets its first reviewer assignment (via the admin's Assign action).
     */
    public function created(Review $review): void
    {
        if (! $review->reviewer_id) {
            return;
        }

        // Don't email a reviewer for creating their own review row
        // (e.g. ReviewWorkspace's firstOrCreate when opening a submission
        // that was already assigned to them).
        if ($review->reviewer_id === Auth::id()) {
            return;
        }

        $this->sendAssignedEmail($review->id, $review->reviewer_id);
    }

    /**
     * Fired when an existing Review is updated — covers reassignment via
     * the admin's Assign/Reassign action (single or bulk).
     */
    public function updated(Review $review): void
    {
        if (! $review->wasChanged('reviewer_id')) {
            return;
        }

        $oldReviewerId = $review->getOriginal('reviewer_id');
        $newReviewerId = $review->reviewer_id;

        // ── Notify the OLD reviewer they've been unassigned ─────────────────
        if ($oldReviewerId && $oldReviewerId !== $newReviewerId && $oldReviewerId !== Auth::id()) {
            $this->sendUnassignedEmail($review->submission_id, $oldReviewerId);
        }

        // ── Notify the NEW reviewer they've been assigned ───────────────────
        if ($newReviewerId && $newReviewerId !== $oldReviewerId && $newReviewerId !== Auth::id()) {
            $this->sendAssignedEmail($review->id, $newReviewerId);
        }
    }

    // ── Mail dispatch helpers — both non-blocking ────────────────────────────

    private function sendAssignedEmail(int $reviewId, int $reviewerId): void
    {
        dispatch(function () use ($reviewId, $reviewerId) {
            try {
                $review = Review::with(['reviewer', 'submission.student', 'submission.task.section.trainingProgram'])
                    ->find($reviewId);

                $reviewer = $review?->reviewer ?? User::find($reviewerId);

                if (! $review || ! $reviewer?->email) {
                    Log::warning('ReviewObserver: cannot send assigned email — missing data', [
                        'event'     => 'reviewer_assigned_email_skipped',
                        'review_id' => $reviewId,
                    ]);
                    return;
                }

                Mail::to($reviewer->email)->send(new ReviewerAssignedMail($review));

                Log::info('ReviewObserver: reviewer assigned email sent', [
                    'event'         => 'reviewer_assigned_email_sent',
                    'review_id'     => $reviewId,
                    'reviewer_id'   => $reviewerId,
                    'reviewer_email'=> $reviewer->email,
                ]);
            } catch (\Exception $e) {
                Log::error('ReviewObserver: reviewer assigned email failed', [
                    'event'     => 'reviewer_assigned_email_failed',
                    'review_id' => $reviewId,
                    'error'     => $e->getMessage(),
                ]);
            }
        })->afterResponse();
    }

    private function sendUnassignedEmail(int $submissionId, int $oldReviewerId): void
    {
        dispatch(function () use ($submissionId, $oldReviewerId) {
            try {
                $submission = Submission::with(['student', 'task.section'])->find($submissionId);
                $reviewer   = User::find($oldReviewerId);

                if (! $submission || ! $reviewer?->email) {
                    Log::warning('ReviewObserver: cannot send unassigned email — missing data', [
                        'event'         => 'reviewer_unassigned_email_skipped',
                        'submission_id' => $submissionId,
                    ]);
                    return;
                }

                Mail::to($reviewer->email)->send(new ReviewerUnassignedMail($submission, $reviewer));

                Log::info('ReviewObserver: reviewer unassigned email sent', [
                    'event'          => 'reviewer_unassigned_email_sent',
                    'submission_id'  => $submissionId,
                    'old_reviewer_id'=> $oldReviewerId,
                    'reviewer_email' => $reviewer->email,
                ]);
            } catch (\Exception $e) {
                Log::error('ReviewObserver: reviewer unassigned email failed', [
                    'event'         => 'reviewer_unassigned_email_failed',
                    'submission_id' => $submissionId,
                    'error'         => $e->getMessage(),
                ]);
            }
        })->afterResponse();
    }
}
