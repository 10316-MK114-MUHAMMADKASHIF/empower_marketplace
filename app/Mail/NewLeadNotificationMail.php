<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewLeadNotificationMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Lead $lead) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Lead: '.$this->lead->name.($this->lead->package_interest ? " ({$this->lead->package_interest})" : ''),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.leads.notification',
            with: ['lead' => $this->lead],
        );
    }
}
