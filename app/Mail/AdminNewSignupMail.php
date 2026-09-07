<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminNewSignupMail extends Mailable
{
    use SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Signup — '.$this->user->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.admin.new-signup',
            with: ['user' => $this->user],
        );
    }
}
