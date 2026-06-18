<?php

namespace App\Mail;

use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VehicleApprovedMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Vehicle $vehicle) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Vehicle Listing Has Been Approved');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.vehicle-approved');
    }
}
