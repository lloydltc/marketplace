<?php

namespace App\Console\Commands;

use App\Modules\Orders\Models\Order;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Console\Command;

/**
 * Phase 14 §14.3: vendor-fulfilled orders that have been delivered but not
 * confirmed by the buyer auto-complete after a configurable number of days,
 * so vendors still settle even when buyers go quiet.
 */
class AutoCompleteVendorOrders extends Command
{
    protected $signature = 'orders:auto-complete-vf';

    protected $description = 'Auto-complete delivered vendor-fulfilled orders past the confirmation window';

    public function handle(SettingsService $settings): int
    {
        $days   = $settings->getInt('delivery.vf_auto_complete_days', 7);
        $cutoff = now()->subDays($days);

        $orders = Order::where('fulfilment_track', 'vendor')
            ->where('status', 'delivered')
            ->whereNotNull('delivered_at')
            ->where('delivered_at', '<=', $cutoff)
            ->get();

        $completed = 0;
        foreach ($orders as $order) {
            if ($order->canTransitionTo('completed')) {
                $order->transitionTo('completed');
                $completed++;
            }
        }

        $this->info("Auto-completed {$completed} vendor-fulfilled order(s).");

        return self::SUCCESS;
    }
}
