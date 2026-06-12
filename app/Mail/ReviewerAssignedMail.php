<?php

namespace App\Mail;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReviewerAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Review $review) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [
                new Address(config('mail.from.address'), config('mail.from.name')),
            ],
            subject: 'New Submission Assigned to You — ' . ($this->review->submission?->task?->title ?? 'Review'),
        );
    }

    public function content(): Content
    {
        $submission = $this->review->submission;
        $task       = $submission?->task;

        return new Content(
            view: 'emails.reviewer-assigned',
            with: [
                'reviewerName' => $this->review->reviewer?->name,
                'candidateName' => $submission?->student?->name,
                'taskTitle'     => $task?->title,
                'sectionName'   => $task?->section?->name,
                'programName'   => $task?->section?->trainingProgram?->name,
                'submittedAt'   => $submission?->submitted_at?->format('M j, Y · g:i A'),
                'dueDate'       => $task?->due_date?->format('M j, Y'),
                'queueUrl'      => url('/reviewer/review-queue'),
                'portalName'    => config('app.name', 'MG Portfolio'),
            ],
        );
    }
}
