<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\OrderFormPdfGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** A new order's Order Form PDF, sent to Finance (and the copy addresses) as soon as it is placed. */
class OrderFormMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        $practice = $this->order->user?->practice?->name ?: $this->order->user?->name;

        return new Envelope(subject: "New Order Form — {$practice} (Order #{$this->order->id})");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.admin.order-form', with: ['order' => $this->order->loadMissing(['package', 'user.practice'])]);
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => app(OrderFormPdfGenerator::class)->generate($this->order), "order-form-{$this->order->id}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
