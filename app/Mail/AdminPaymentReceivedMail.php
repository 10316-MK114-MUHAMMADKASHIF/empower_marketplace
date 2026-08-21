<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminPaymentReceivedMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Payment Received — '.($this->order->package?->name ?? 'Compliance Package'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.admin.payment-received',
            with: ['order' => $this->order],
        );
    }
}
