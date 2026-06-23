<?php

namespace App\Notifications;

use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * D5: tells a seller their vehicle listing is about to expire ('expiring') or has
 * expired ('expired'), with a prompt to renew.
 */
class VehicleListingNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Vehicle $vehicle,
        public readonly string $kind, // 'expiring' | 'expired'
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $title = $this->vehicle->displayTitle();

        if ($this->kind === 'expiring') {
            $days = $this->vehicle->expires_at
                ? max(1, (int) ceil(now()->floatDiffInDays($this->vehicle->expires_at)))
                : null;

            return (new MailMessage())
                ->subject('Your SalmaDrive listing is about to expire')
                ->greeting('Hi ' . ($notifiable->name ?? 'there') . ',')
                ->line("Your listing \"{$title}\" expires" . ($days ? " in {$days} day(s)" : ' soon') . '.')
                ->line('Renew it from your dashboard to keep it visible to buyers.')
                ->action('Go to my listings', url('/'));
        }

        return (new MailMessage())
            ->subject('Your SalmaDrive listing has expired')
            ->greeting('Hi ' . ($notifiable->name ?? 'there') . ',')
            ->line("Your listing \"{$title}\" has expired and is no longer shown to buyers.")
            ->line('You can renew it any time from your dashboard — it’s free.')
            ->action('Renew my listing', url('/'));
    }
}
