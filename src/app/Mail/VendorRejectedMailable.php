<?php

namespace App\Mail;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VendorRejectedMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Vendor $vendor,
        public readonly string $reason
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Update on Your SalmaDrive Vendor Application');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.vendor-rejected');
    }
}
