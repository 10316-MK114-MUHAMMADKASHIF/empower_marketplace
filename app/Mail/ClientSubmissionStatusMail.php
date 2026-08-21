<?php

namespace App\Mail;

use App\Enums\IntakeSubmissionStatus;
use App\Models\IntakeSubmission;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientSubmissionStatusMail extends Mailable
{
    use SerializesModels;

    public function __construct(public IntakeSubmission $submission) {}

    public function envelope(): Envelope
    {
        $subject = $this->submission->status === IntakeSubmissionStatus::Approved
            ? 'Your Intake Documents Have Been Approved'
            : 'Action Needed on Your Intake Documents';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.submissions.status-changed',
            with: ['submission' => $this->submission],
        );
    }
}
