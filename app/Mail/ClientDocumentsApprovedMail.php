<?php

namespace App\Mail;

use App\Models\GeneratedDocument;
use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientDocumentsApprovedMail extends Mailable
{
    use SerializesModels;

    /** @param Collection<int, GeneratedDocument> $documents */
    public function __construct(public Order $order, public Collection $documents) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Compliance Documents Are Ready — '.($this->order->package?->name ?? 'Compliance Package'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.documents.approved',
            with: [
                'order' => $this->order,
                'documents' => $this->documents,
            ],
        );
    }
}
