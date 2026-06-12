<?php

namespace App\Mail;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubmissionNeedsRevisionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Submission $submission) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [
                new Address(config('mail.from.address'), config('mail.from.name')),
            ],
            subject: 'Action Needed: Revise Your Submission — ' . $this->submission->task->title,
        );
    }

    public function content(): Content
    {
        $review = $this->submission->review;

        return new Content(
            view: 'emails.revision',
            with: [
                'candidateName' => $this->submission->student->name,
                'taskTitle'     => $this->submission->task->title,
                'sectionName'   => $this->submission->task->section->name ?? null,
                'comments'      => $review?->comments,
                'reviewerName'  => $review?->reviewer?->name,
                'dueDate'       => $this->submission->task->due_date?->format('M j, Y'),
                'taskUrl'       => url('/student/tasks/' . $this->submission->task_id),
                'portalName'    => config('app.name', 'MG Portfolio'),
            ],
        );
    }
}
