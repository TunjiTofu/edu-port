<?php

namespace App\Console\Commands;

use App\Enums\RoleTypes;
use App\Enums\SubmissionTypes;
use App\Mail\ReviewerWeeklyReminderMail;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWeeklyReviewerReminders extends Command
{
    protected $signature   = 'reviewer:send-weekly-reminders';
    protected $description = 'Send a weekly reminder email to every reviewer who has pending submissions in their queue.';

    public function handle(): int
    {
        $this->info('Sending weekly reviewer reminders...');

        // Get all active reviewers who have at least one pending or under-review submission
        $reviewers = User::whereHas('role', fn ($q) => $q->where('name', RoleTypes::REVIEWER->value))
            ->where('is_active', true)
            ->whereHas('reviews', fn ($q) =>
            $q->whereHas('submission', fn ($s) =>
            $s->whereIn('status', [
                SubmissionTypes::PENDING_REVIEW->value,
                SubmissionTypes::UNDER_REVIEW->value,
            ])
            )
            )
            ->get();

        if ($reviewers->isEmpty()) {
            $this->info('No reviewers with pending submissions — nothing to send.');
            return self::SUCCESS;
        }

        $sent   = 0;
        $failed = 0;

        foreach ($reviewers as $reviewer) {
            // Load their pending submissions (current year by default)
            $pending = Submission::whereHas('review', fn ($q) => $q->where('reviewer_id', $reviewer->id))
                ->whereIn('status', [
                    SubmissionTypes::PENDING_REVIEW->value,
                    SubmissionTypes::UNDER_REVIEW->value,
                ])
                ->whereYear('submitted_at', now()->year)
                ->with(['student', 'task.section.trainingProgram'])
                ->orderBy('submitted_at', 'asc') // oldest first
                ->get();

            if ($pending->isEmpty()) {
                continue;
            }

            try {
                // Map to a plain array before sending — same reason as BulkReviewerAssignedMail:
                // SerializesModels strips eager-loaded relations if models are passed directly.
                $submissionsData = $pending->map(fn ($s) => [
                    'candidate' => $s->student?->name ?? '—',
                    'task'      => $s->task?->title ?? '—',
                    'section'   => $s->task?->section?->name ?? '—',
                    'submitted' => $s->submitted_at?->format('M j, Y') ?? '—',
                    'waiting'   => $s->submitted_at
                        ? $s->submitted_at->diffForHumans(now(), true)
                        : '—',
                ])->values()->all();

                Mail::to($reviewer->email)->send(new ReviewerWeeklyReminderMail($reviewer, $submissionsData));

                Log::info('WeeklyReminder: sent', [
                    'event'        => 'weekly_reminder_sent',
                    'reviewer_id'  => $reviewer->id,
                    'email'        => $reviewer->email,
                    'pending_count'=> $pending->count(),
                ]);

                $this->line("  ✓ {$reviewer->name} ({$reviewer->email}) — {$pending->count()} pending");
                $sent++;

            } catch (\Exception $e) {
                Log::error('WeeklyReminder: failed', [
                    'event'       => 'weekly_reminder_failed',
                    'reviewer_id' => $reviewer->id,
                    'email'       => $reviewer->email,
                    'error'       => $e->getMessage(),
                ]);

                $this->error("  ✗ {$reviewer->name} ({$reviewer->email}): {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Done. {$sent} reminder(s) sent, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
