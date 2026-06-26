<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BulkReviewerAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param User  $reviewer         The reviewer being assigned
     * @param array $submissionsData  Pre-mapped plain array — NOT Eloquent models.
     *                                Mapping must happen BEFORE constructing this
     *                                mailable so SerializesModels never touches the
     *                                relations and strips them.
     *
     *  Each item: ['candidate', 'task', 'section', 'program', 'submitted']
     */
    public function __construct(
        public User  $reviewer,
        public array $submissionsData,
    ) {}

    public function envelope(): Envelope
    {
        $count = count($this->submissionsData);

        return new Envelope(
            subject: "{$count} New Submission(s) Assigned to You",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.bulk-reviewer-assigned',
            with: [
                'reviewerName' => $this->reviewer->name,
                'submissions'  => $this->submissionsData,
                'count'        => count($this->submissionsData),
                'queueUrl'     => url('/reviewer/review-queue'),
                'portalName'   => config('app.name', 'MG Portfolio'),
            ],
        );
    }
}
