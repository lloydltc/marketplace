<?php

namespace App\Notifications;

use App\Modules\Verification\Models\VendorVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * VB2: warns a vendor that a verification dimension is about to expire and needs
 * re-verification to keep their badge tier.
 */
class VerificationExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly VendorVerification $verification) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dimension = str_replace('_', ' ', $this->verification->dimension);
        $when = $this->verification->expires_at?->toFormattedDateString();

        return (new MailMessage())
            ->subject('Your SalmaDrive verification is expiring')
            ->greeting('Hi ' . ($notifiable->name ?? 'there') . ',')
            ->line("Your {$dimension} verification expires on {$when}.")
            ->line('Re-verify before then to keep your trust badge.')
            ->action('Manage verification', url('/'));
    }
}
