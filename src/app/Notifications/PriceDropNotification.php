<?php

namespace App\Notifications;

use App\Models\SavedSearch;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * AC2: a vehicle matching one of the buyer's saved searches has dropped in price.
 * Fans out across the buyer's chosen channels via the shared architecture.
 */
class PriceDropNotification extends ChannelAwareNotification
{
    public function __construct(
        public readonly SavedSearch $search,
        public readonly Vehicle $vehicle,
        public readonly float $oldPrice,
        public readonly float $newPrice,
        public readonly string $currency,
    ) {}

    public function type(): string
    {
        return 'alert.price_drop';
    }

    /** @return array<string, mixed> */
    public function payload(object $notifiable): array
    {
        return [
            'title' => 'Price drop: ' . $this->vehicle->displayTitle(),
            'body'  => "Now {$this->currency} " . number_format($this->newPrice, 2)
                . " (was {$this->currency} " . number_format($this->oldPrice, 2) . ') — matches "' . $this->search->name . '"',
            'url'   => route('vehicles.show', $this->vehicle),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Price drop on a vehicle you’re watching')
            ->greeting('Hi ' . ($notifiable->name ?? 'there') . ',')
            ->line($this->vehicle->displayTitle() . ' dropped to ' . $this->currency . ' ' . number_format($this->newPrice, 2)
                . ' (was ' . $this->currency . ' ' . number_format($this->oldPrice, 2) . ').')
            ->line('It matches your saved search “' . $this->search->name . '”.')
            ->action('View listing', route('vehicles.show', $this->vehicle));
    }
}
