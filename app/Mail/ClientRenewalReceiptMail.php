<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\ReceiptPdfGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientRenewalReceiptMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Subscription Renewed — '.($this->order->package?->name ?? 'Compliance Package'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.client.renewal-receipt',
            with: ['order' => $this->order],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => app(ReceiptPdfGenerator::class)->generate($this->order),
                "receipt-{$this->order->id}.pdf",
            )->withMime('application/pdf'),
        ];
    }
}
