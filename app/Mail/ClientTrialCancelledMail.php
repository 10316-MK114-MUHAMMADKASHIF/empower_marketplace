<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientTrialCancelledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @param  'client_requested'|'trial_expired'  $reason */
    public function __construct(public Order $order, public string $reason) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Free Trial Has Ended — '.($this->order->package?->name ?? 'Compliance Package'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.client.trial-cancelled',
            with: ['order' => $this->order, 'reason' => $this->reason],
        );
    }
}
