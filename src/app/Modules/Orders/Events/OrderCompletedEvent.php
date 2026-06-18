<?php

namespace App\Modules\Orders\Events;

use App\Modules\Orders\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * The settlement event. Fired exactly once when an order reaches "completed"
 * (guarded by orders.settled_at). The Phase 13 wallet consumes this to move
 * money: SALE_CREDIT for platform-collected orders, COMMISSION_DEBIT for VF-COD.
 */
class OrderCompletedEvent
{
    use Dispatchable;

    public function __construct(public readonly Order $order) {}
}
