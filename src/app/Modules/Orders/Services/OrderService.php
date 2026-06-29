<?php

namespace App\Modules\Orders\Services;

use App\Modules\Cart\DTO\CartGroup;
use App\Modules\Commerce\Services\CommissionCalculator;
use App\Modules\Orders\Models\Order;
use App\Modules\Products\Services\InventoryService;
use App\Modules\Rfq\Models\Quote;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Turns validated cart groups into orders — one order per group (per vendor +
 * fulfilment track). Each order snapshots its commission immutably at creation,
 * so later rate changes never alter historical orders (BUSINESS_MODEL.md §5).
 */
class OrderService
{
    public function __construct(
        private readonly CommissionCalculator $commission,
        private readonly SettingsService $settings,
        private readonly InventoryService $inventory
    ) {}

    /**
     * @param  CartGroup[]  $groups
     * @param  array<string, array{fulfilment: string, payment: string}>  $selections
     * @param  array<string, mixed>  $customer
     * @return Order[]
     */
    public function createFromCart(array $groups, array $selections, array $customer, ?string $buyerUserId): array
    {
        return DB::transaction(function () use ($groups, $selections, $customer, $buyerUserId) {
            $orders = [];

            foreach ($groups as $group) {
                $orders[] = $this->createOrder($group, $selections[$group->key()], $customer, $buyerUserId);
            }

            return $orders;
        });
    }

    /**
     * Convert an accepted RFQ quote into a normal order. The result is
     * structurally identical to a cart order — same commission snapshot, same
     * settlement path — so the settlement engine cannot tell them apart
     * (BUSINESS_MODEL.md §5, §6). RFQ parts are vendor-fulfilled and prepaid.
     *
     * @param  array<string, mixed>  $customer
     */
    public function createFromQuote(Quote $quote, array $customer, ?string $buyerUserId): Order
    {
        return DB::transaction(function () use ($quote, $customer, $buyerUserId) {
            $vendor      = $quote->vendor;
            $request     = $quote->partRequest;
            $commission  = $this->commission->forLines($vendor, [
                ['line_total' => (float) $quote->price, 'category' => null],
            ]);
            $subtotal    = $commission['subtotal'];

            $order = Order::create([
                'buyer_user_id'           => $buyerUserId,
                'buyer_name'              => $customer['full_name'],
                'buyer_email'             => $customer['email'],
                'buyer_phone'             => $customer['phone'],
                'buyer_address'           => $customer['address'],
                'buyer_city'              => $customer['city'],
                'vendor_id'               => $vendor->id,
                'fulfilment_track'        => 'vendor',
                'payment_method'          => 'prepaid',
                'status'                  => 'pending_payment',
                'currency'                => 'ZWL',
                'subtotal'                => $subtotal,
                'delivery_fee'            => 0,
                'total'                   => $subtotal,
                'commission_rate_applied' => $commission['rate'],
                'commission_amount'       => $commission['amount'],
                'net_to_vendor'           => $commission['net'],
            ]);

            $order->items()->create([
                'product_id' => null,
                'title'      => 'Part request: ' . Str::limit($request->part_description, 80),
                'unit_price' => $quote->price,
                'quantity'   => 1,
                'line_total' => $quote->price,
            ]);

            return $order;
        });
    }

    /**
     * @param  array{fulfilment: string, payment: string}  $selection
     * @param  array<string, mixed>  $customer
     */
    private function createOrder(CartGroup $group, array $selection, array $customer, ?string $buyerUserId): Order
    {
        $track   = $selection['fulfilment'];
        $payment = $selection['payment'];
        $vendor  = $group->lines[0]->product->vendor;

        $deliveryFee = $track === 'fbs'
            ? $this->settings->getDecimal('delivery.fbs_default_fee')
            : 0.0;

        // Snapshot commission per line (vendor → category → platform default).
        $commission = $this->commission->forLines($vendor, array_map(fn ($line) => [
            'line_total' => $line->lineTotal(),
            'category'   => $line->product->category,
        ], $group->lines));

        $subtotal = $commission['subtotal'];

        $order = Order::create([
            'buyer_user_id'           => $buyerUserId,
            'buyer_name'              => $customer['full_name'],
            'buyer_email'             => $customer['email'],
            'buyer_phone'             => $customer['phone'],
            'buyer_address'           => $customer['address'],
            'buyer_city'              => $customer['city'],
            'vendor_id'               => $group->vendorId,
            'fulfilment_track'        => $track,
            'payment_method'          => $payment,
            'status'                  => $payment === 'cod' ? 'cod_pending' : 'pending_payment',
            'currency'                => 'ZWL',
            'subtotal'                => $subtotal,
            'delivery_fee'            => $deliveryFee,
            'total'                   => round($subtotal + $deliveryFee, 2),
            'commission_rate_applied' => $commission['rate'],
            'commission_amount'       => $commission['amount'],
            'net_to_vendor'           => $commission['net'],
        ]);

        foreach ($group->lines as $line) {
            $order->items()->create([
                'product_id' => $line->product->id,
                'title'      => $line->product->title,
                'unit_price' => $line->product->price_zwl,
                'quantity'   => $line->quantity,
                'line_total' => $line->lineTotal(),
            ]);

            // PM2/PM10: hold stock for canonical-parts offerings (managed inventory).
            // Legacy products without a part_id are untouched (no behaviour change).
            if ($line->product->part_id !== null) {
                $this->inventory->reserve($line->product, $line->quantity, 'order:' . $order->id);
            }
        }

        return $order;
    }
}
