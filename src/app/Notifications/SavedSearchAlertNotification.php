<?php

namespace App\Notifications;

use App\Models\SavedSearch;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * H7: emails a buyer the new listings that matched one of their saved searches
 * since the last digest. Doubles as a price alert when the saved search carries a
 * max-price filter.
 */
class SavedSearchAlertNotification extends Notification
{
    use Queueable;

    /**
     * @param  Collection<int, \App\Modules\Vehicles\Models\Vehicle>  $matches
     */
    public function __construct(
        public readonly SavedSearch $search,
        public readonly Collection $matches,
        public readonly int $totalNew,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $this->search->name;

        $mail = (new MailMessage())
            ->subject("New matches for your saved search “{$name}”")
            ->greeting('Hi ' . ($notifiable->name ?? 'there') . ',')
            ->line("We found {$this->totalNew} new listing(s) matching your saved search “{$name}”.");

        foreach ($this->matches as $vehicle) {
            $mail->line('• ' . $vehicle->displayTitle() . ' — ' . $vehicle->primaryPrice());
        }

        if ($this->totalNew > $this->matches->count()) {
            $mail->line('…and more.');
        }

        return $mail
            ->action('View all matches', $this->search->url())
            ->line('Manage or turn off this alert from your saved searches.');
    }
}
