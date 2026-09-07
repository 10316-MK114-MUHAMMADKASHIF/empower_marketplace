<?php

namespace App\Mail;

use App\Models\IntakeSubmission;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminIntakeSubmittedMail extends Mailable
{
    use SerializesModels;

    public function __construct(public IntakeSubmission $submission) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Intake Documents Submitted — Order #'.$this->submission->order_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.admin.intake-submitted',
            with: ['submission' => $this->submission],
        );
    }
}
