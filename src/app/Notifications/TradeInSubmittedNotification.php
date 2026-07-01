<?php

namespace App\Notifications;

use App\Modules\TradeIn\Models\TradeIn;
use Illuminate\Notifications\Messages\MailMessage;

/** TI2: notifies verified dealers of a new trade-in they can bid on. */
class TradeInSubmittedNotification extends ChannelAwareNotification
{
    public function __construct(public readonly TradeIn $tradeIn) {}

    public function type(): string
    {
        return 'trade_in.submitted';
    }

    public function payload(object $notifiable): array
    {
        return [
            'title' => 'New trade-in: ' . $this->tradeIn->title(),
            'body'  => number_format($this->tradeIn->mileage) . ' km · ' . ucfirst($this->tradeIn->condition),
            'url'   => route('vendor.trade-ins.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('New trade-in available to bid on')
            ->line($this->tradeIn->title() . ' (' . number_format($this->tradeIn->mileage) . ' km, ' . ucfirst($this->tradeIn->condition) . ')')
            ->action('View & bid', route('vendor.trade-ins.index'));
    }
}
