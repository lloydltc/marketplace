<?php

namespace App\Modules\Wallet\Listeners;

use App\Modules\Orders\Events\OrderCompletedEvent;
use App\Modules\Wallet\Services\SettlementService;

class SettleCompletedOrder
{
    public function __construct(private readonly SettlementService $settlement) {}

    public function handle(OrderCompletedEvent $event): void
    {
        $this->settlement->settle($event->order);
    }
}
