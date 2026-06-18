<?php

namespace App\Modules\Cart\DTO;

/**
 * One per-vendor, per-fulfilment-track group in the cart. At checkout each
 * group becomes exactly one order (a single checkout may produce several).
 */
final class CartGroup
{
    /**
     * @param  CartLine[]  $lines
     * @param  string  $track  fbs | vendor | both
     */
    public function __construct(
        public readonly string $vendorId,
        public readonly string $vendorName,
        public readonly string $track,
        public readonly array $lines,
        public readonly ?float $deliveryFee,
        public readonly string $deliveryLabel,
        public readonly bool $codAvailable,
        public readonly bool $vendorCodEligible = false,
    ) {}

    /**
     * Stable identifier for this group within a cart (vendor + track).
     * Used as the form key when selecting fulfilment/payment at checkout.
     */
    public function key(): string
    {
        return $this->vendorId . '|' . $this->track;
    }

    /**
     * True only if every line's product permits cash on delivery.
     */
    public function allCodAllowed(): bool
    {
        return array_reduce(
            $this->lines,
            fn (bool $carry, CartLine $l) => $carry && (bool) $l->product->cod_allowed,
            true
        );
    }

    public function subtotal(): float
    {
        return array_sum(array_map(fn (CartLine $l) => $l->lineTotal(), $this->lines));
    }

    public function itemCount(): int
    {
        return array_sum(array_map(fn (CartLine $l) => $l->quantity, $this->lines));
    }

    public function total(): float
    {
        return $this->subtotal() + ($this->deliveryFee ?? 0.0);
    }
}
