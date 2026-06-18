<?php

namespace App\Mail;

use App\Models\VendorInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class VendorInvitationMailable extends Mailable
{
    use Queueable, SerializesModels;

    public string $acceptUrl;

    public function __construct(public VendorInvitation $invitation)
    {
        $this->acceptUrl = (string) URL::temporarySignedRoute(
            'vendor.invitation.accept',
            now()->addHours(48),
            ['token' => $invitation->token]
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: $this->invitation->email,
            subject: 'You\'ve been invited to join ' . $this->invitation->vendor->name . ' on SalmaDrive'
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.vendor-invitation');
    }
}
