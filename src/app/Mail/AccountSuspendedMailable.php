<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountSuspendedMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $reason = 'Your account has been suspended due to a policy violation.'
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your SalmaDrive Account Has Been Suspended');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.account-suspended');
    }
}
