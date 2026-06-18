<?php

namespace App\Mail;

use App\Modules\Products\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProductApprovedMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Product $product) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Product Has Been Approved');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.product-approved');
    }
}
