<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeCredentialsMail extends Mailable
{
    use SerializesModels;

    public function __construct(public User $user, public string $password) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to '.config('app.name').' — Your Login Details',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.auth.welcome-credentials',
            with: [
                'user' => $this->user,
                'password' => $this->password,
                'loginUrl' => route('login'),
            ],
        );
    }
}
