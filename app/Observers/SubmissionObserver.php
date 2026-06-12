<?php

namespace App\Observers;

use App\Enums\SubmissionTypes;
use App\Mail\SubmissionNeedsRevisionMail;
use App\Models\Submission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SubmissionObserver
{
    /**
     * Fired whenever a Submission is updated — from anywhere:
     * the reviewer's review workspace, the admin's quick-edit,
     * or any other code path. This guarantees the email fires
     * exactly once per transition into "needs_revision", no
     * matter which UI triggered the change.
     */
    public function updated(Submission $submission): void
    {
        // Unconditional diagnostic log — confirms the observer is firing at all.
        // If this line never appears in storage/logs/laravel.log after a save,
        // the observer is NOT registered (check AppServiceProvider deployment
        // and run `php artisan optimize:clear`).
        Log::info('SubmissionObserver: updated() fired', [
            'event'         => 'submission_observer_fired',
            'submission_id' => $submission->id,
            'changed'       => $submission->getChanges(),
            'status_changed'=> $submission->wasChanged('status'),
            'old_status'    => $submission->getOriginal('status'),
            'new_status'    => $submission->status,
        ]);

        // Only act if 'status' actually changed in this update
        if (! $submission->wasChanged('status')) {
            return;
        }

        $newStatus = $submission->status;
        $oldStatus = $submission->getOriginal('status');

        // Only send when transitioning INTO needs_revision
        // (not if it was already needs_revision and something else changed)
        if ($newStatus !== SubmissionTypes::NEEDS_REVISION->value) {
            return;
        }

        if ($oldStatus === SubmissionTypes::NEEDS_REVISION->value) {
            return; // no actual transition — avoid duplicate emails
        }

        $this->sendNeedsRevisionEmail($submission);
    }

    /**
     * Non-blocking email dispatch — never delays the reviewer's save action
     * and never surfaces a mail failure to the UI.
     */
    private function sendNeedsRevisionEmail(Submission $submission): void
    {
        $submissionId = $submission->id;

        // Reload relations fresh inside the closure to avoid serialization issues
        dispatch(function () use ($submissionId) {
            try {
                $fresh = Submission::with(['student', 'task.section', 'review.reviewer'])
                    ->find($submissionId);

                if (! $fresh || ! $fresh->student?->email) {
                    Log::warning('SubmissionObserver: cannot send needs-revision email — missing data', [
                        'event'         => 'needs_revision_email_skipped',
                        'submission_id' => $submissionId,
                    ]);
                    return;
                }

                Mail::to($fresh->student->email)
                    ->send(new SubmissionNeedsRevisionMail($fresh));

                Log::info('SubmissionObserver: needs-revision email sent', [
                    'event'         => 'needs_revision_email_sent',
                    'submission_id' => $submissionId,
                    'student_email' => $fresh->student->email,
                ]);

            } catch (\Exception $e) {
                Log::error('SubmissionObserver: needs-revision email failed', [
                    'event'         => 'needs_revision_email_failed',
                    'submission_id' => $submissionId,
                    'error'         => $e->getMessage(),
                ]);
                // Swallow — email failure must never block the reviewer's workflow
            }
        })->afterResponse();
    }
}
