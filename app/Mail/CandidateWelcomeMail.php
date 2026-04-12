<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CandidateWelcomeMail extends Mailable
{
    // Queueable is kept so you CAN queue this with a worker.
    // On cPanel with no queue worker, set QUEUE_CONNECTION=sync in .env
    // so this sends immediately in the same request.
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [
                new Address(
                    config('mail.from.address'),
                    config('mail.from.name')
                ),
            ],
            subject: 'Welcome to Ogun Conference MG Portfolio Portal — Your Account is Ready',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.candidate-welcome',
            with: [
                'name'       => $this->user->name,
                'email'      => $this->user->email,
                'loginUrl'   => url('/student/login'),
                'portalName' => config('app.name', 'MG Portfolio'),
            ],
        );
    }
}
