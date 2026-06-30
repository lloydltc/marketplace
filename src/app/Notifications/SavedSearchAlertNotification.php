<?php

namespace App\Notifications;

use App\Models\SavedSearch;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;

/**
 * H7 + AC2: tells a buyer about new listings matching a saved search since the
 * last digest. Fans out across the buyer's chosen channels (in-app + email by
 * default) via the shared notification architecture.
 */
class SavedSearchAlertNotification extends ChannelAwareNotification
{
    /**
     * @param  Collection<int, \App\Modules\Vehicles\Models\Vehicle>  $matches
     */
    public function __construct(
        public readonly SavedSearch $search,
        public readonly Collection $matches,
        public readonly int $totalNew,
    ) {}

    public function type(): string
    {
        return 'alert.new_match';
    }

    /** @return array<string, mixed> */
    public function payload(object $notifiable): array
    {
        return [
            'title' => "{$this->totalNew} new match(es) for “{$this->search->name}”",
            'body'  => $this->matches->map->displayTitle()->take(3)->implode(', '),
            'url'   => $this->search->url(),
        ];
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
