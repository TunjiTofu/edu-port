<?php

namespace App\Mail;

use App\Models\Submission;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReviewerUnassignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Submission $submission,
        public User $reviewer,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [
                new Address(config('mail.from.address'), config('mail.from.name')),
            ],
            subject: 'Submission Reassigned — ' . ($this->submission->task?->title ?? 'Review'),
        );
    }

    public function content(): Content
    {
        $task = $this->submission->task;

        return new Content(
            view: 'emails.reviewer-unassigned',
            with: [
                'reviewerName'  => $this->reviewer->name,
                'candidateName' => $this->submission->student?->name,
                'taskTitle'     => $task?->title,
                'sectionName'   => $task?->section?->name,
                'queueUrl'      => url('/reviewer/review-queue'),
                'portalName'    => config('app.name', 'MG Portfolio'),
            ],
        );
    }
}
