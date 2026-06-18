<?php

namespace App\Mail;

use App\Modules\Products\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProductRejectedMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Product $product,
        public readonly string $reason
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Product Listing Was Not Approved');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.product-rejected');
    }
}
