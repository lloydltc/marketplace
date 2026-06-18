<?php

namespace App\Mail;

use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VehicleRejectedMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Vehicle $vehicle,
        public readonly string $reason
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Vehicle Listing Was Not Approved');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.vehicle-rejected');
    }
}
