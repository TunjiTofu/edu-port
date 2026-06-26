<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReviewerWeeklyReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param User  $reviewer The reviewer
     * @param array $submissionsData Pre-mapped plain array — NOT Eloquent models.
     *
     * Each item: ['candidate', 'task', 'section', 'submitted', 'waiting']
     */
    public function __construct(
        public User  $reviewer,
        public array $submissionsData,
    ) {}

    public function envelope(): Envelope
    {
        $count = count($this->submissionsData);

        return new Envelope(
            subject: "⏰ Reminder: You Have {$count} Pending Review(s) — " . now()->format('M j, Y'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reviewer-weekly-reminder',
            with: [
                'reviewerName'  => $this->reviewer->name,
                'submissions'   => $this->submissionsData,
                'count'         => count($this->submissionsData),
                'queueUrl'      => url('/reviewer/review-queue'),
                'portalName'    => config('app.name', 'MG Portfolio'),
            ],
        );
    }
}
