<?php

namespace App\Mail;

use App\Models\DiscountCode;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DiscountCodeSharedMail extends Mailable
{
    use SerializesModels;

    public function __construct(public DiscountCode $discountCode, public ?string $recipientName = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'A discount code for '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.discount-codes.shared',
            with: [
                'discountCode' => $this->discountCode,
                'recipientName' => $this->recipientName,
                'homeUrl' => route('home'),
            ],
        );
    }
}
