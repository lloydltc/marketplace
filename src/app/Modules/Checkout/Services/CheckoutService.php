<?php

namespace App\Modules\Checkout\Services;

use App\Modules\Cart\DTO\CartGroup;
use App\Modules\Cart\Services\CartService;
use App\Modules\Checkout\Exceptions\CheckoutValidationException;
use App\Modules\Settings\Services\SettingsService;

/**
 * Drives Phase 10 checkout: it turns the cart's per-vendor/per-track groups into
 * selectable fulfilment + payment options, enforces the COD matrix server-side,
 * and builds a session-safe order summary for the Phase 11 payment handoff.
 *
 * No orders are created here — order creation is Phase 12, payment is Phase 11.
 */
class CheckoutService
{
    public function __construct(
        private readonly CartService $cart,
        private readonly SettingsService $settings
    ) {}

    /**
     * The fulfilment tracks a group may be shipped on, each with whether COD is
     * permitted and the delivery fee (from settings) for that track.
     *
     * @return array<string, array{cod: bool, deliveryFee: ?float}>
     */
    public function fulfilmentChoices(CartGroup $group): array
    {
        $tracks = match ($group->track) {
            'fbs'    => ['fbs'],
            'vendor' => ['vendor'],
            default  => ['fbs', 'vendor'], // 'both'
        };

        $choices = [];
        foreach ($tracks as $track) {
            $choices[$track] = [
                'cod'         => $this->codAllowedForTrack($group, $track),
                'deliveryFee' => $track === 'fbs'
                    ? $this->settings->getDecimal('delivery.fbs_default_fee')
                    : null,
            ];
        }

        return $choices;
    }

    /**
     * Apply the fulfilment matrix to a specific chosen track:
     *  - FBS COD: rider collects for the platform → only the FBS COD switch + product flag.
     *  - VF COD: vendor collects cash → also gated on the vendor's wallet standing.
     */
    private function codAllowedForTrack(CartGroup $group, string $track): bool
    {
        if (! $group->allCodAllowed()) {
            return false;
        }

        return match ($track) {
            'fbs'    => $this->settings->getBool('cod.fbs_enabled'),
            'vendor' => $this->settings->getBool('cod.vf_enabled') && $group->vendorCodEligible,
            default  => false,
        };
    }

    /**
     * Validate the buyer's per-group selections and build the order summary.
     * Throws {@see CheckoutValidationException} on any invalid or ineligible
     * combination — the single server-side enforcement point.
     *
     * @param  array<string, array{fulfilment?: string, payment?: string}>  $selections
     * @param  array<string, mixed>  $customer
     * @return array<string, mixed>  session-safe summary (scalars/arrays only)
     */
    public function buildSummary(array $selections, array $customer): array
    {
        $groups = $this->cart->groups();

        if ($groups === []) {
            throw new CheckoutValidationException('Your cart is empty.');
        }

        $summaryGroups = [];
        $grandSubtotal = 0.0;
        $grandDelivery = 0.0;

        foreach ($groups as $group) {
            $choices    = $this->fulfilmentChoices($group);
            $selection  = $selections[$group->key()] ?? [];
            $fulfilment = $selection['fulfilment'] ?? null;
            $payment    = $selection['payment'] ?? 'prepaid';

            if ($fulfilment === null || ! isset($choices[$fulfilment])) {
                throw new CheckoutValidationException("Please choose a delivery option for {$group->vendorName}.");
            }

            if (! in_array($payment, ['prepaid', 'cod'], true)) {
                throw new CheckoutValidationException("Invalid payment method for {$group->vendorName}.");
            }

            // The hard COD enforcement: ineligible combinations cannot proceed,
            // even if the form was tampered with.
            if ($payment === 'cod' && ! $choices[$fulfilment]['cod']) {
                throw new CheckoutValidationException("Cash on delivery isn't available for {$group->vendorName}.");
            }

            $deliveryFee   = (float) ($choices[$fulfilment]['deliveryFee'] ?? 0.0);
            $subtotal      = $group->subtotal();
            $grandSubtotal += $subtotal;
            $grandDelivery += $deliveryFee;

            $summaryGroups[] = [
                'vendorName'      => $group->vendorName,
                'fulfilment'      => $fulfilment,
                'fulfilmentLabel' => $fulfilment === 'fbs' ? 'Fulfilled by Salma' : 'Vendor-fulfilled',
                'payment'         => $payment,
                'paymentLabel'    => $payment === 'cod' ? 'Cash on delivery' : 'Prepaid',
                'deliveryFee'     => $deliveryFee,
                'subtotal'        => $subtotal,
                'total'           => $subtotal + $deliveryFee,
                'lines'           => array_map(fn ($l) => [
                    'title'     => $l->product->title,
                    'quantity'  => $l->quantity,
                    'price'     => (float) $l->product->price_zwl,
                    'lineTotal' => $l->lineTotal(),
                ], $group->lines),
            ];
        }

        // Buyer-facing totals are commission-exclusive: price + delivery only.
        return [
            'customer' => $customer,
            'groups'   => $summaryGroups,
            'subtotal' => $grandSubtotal,
            'delivery' => $grandDelivery,
            'total'    => $grandSubtotal + $grandDelivery,
        ];
    }
}
