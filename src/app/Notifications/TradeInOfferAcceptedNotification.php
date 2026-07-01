<?php

namespace App\Notifications;

use App\Modules\TradeIn\Models\TradeInOffer;
use Illuminate\Notifications\Messages\MailMessage;

/** TI2: notifies a dealer their trade-in offer was accepted. */
class TradeInOfferAcceptedNotification extends ChannelAwareNotification
{
    public function __construct(public readonly TradeInOffer $offer) {}

    public function type(): string
    {
        return 'trade_in.offer';
    }

    public function payload(object $notifiable): array
    {
        return [
            'title' => 'Your trade-in offer was accepted',
            'body'  => $this->offer->tradeIn?->title() . ' — ' . $this->offer->amount(),
            'url'   => route('vendor.trade-ins.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Your trade-in offer was accepted')
            ->line(($this->offer->tradeIn?->title() ?? 'A trade-in') . ' — your offer of ' . $this->offer->amount() . ' was accepted.')
            ->line('Contact the seller to complete the trade-in.');
    }
}
