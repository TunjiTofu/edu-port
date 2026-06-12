<?php

namespace App\Mail;

use App\Models\ReviewModificationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReviewModificationRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ReviewModificationRequest $modificationRequest) {}

    public function envelope(): Envelope
    {
        $submission = $this->modificationRequest->review?->submission;

        return new Envelope(
            subject: 'Review Modification Request — ' . ($submission?->task?->title ?? 'Submission'),
        );
    }

    public function content(): Content
    {
        $review     = $this->modificationRequest->review;
        $submission = $review?->submission;
        $task       = $submission?->task;

        return new Content(
            view: 'emails.review-modification-requested',
            with: [
                'reviewerName'  => $this->modificationRequest->reviewer?->name,
                'candidateName' => $submission?->student?->name,
                'taskTitle'     => $task?->title,
                'sectionName'   => $task?->section?->name,
                'currentScore'  => $review?->score,
                'reason'        => $this->modificationRequest->reason,
                'reviewUrl'     => url('/admin/review-modification-requests'),
                'portalName'    => config('app.name', 'MG Portfolio'),
            ],
        );
    }
}
