<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientRenewalFailedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public bool $cancelled = false) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->cancelled
                ? 'Your Subscription Has Been Cancelled — '.($this->order->package?->name ?? 'Compliance Package')
                : 'We Couldn\'t Process Your Renewal Payment — '.($this->order->package?->name ?? 'Compliance Package'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.client.renewal-failed',
            with: ['order' => $this->order, 'cancelled' => $this->cancelled],
        );
    }
}
