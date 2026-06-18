<?php

namespace App\Modules\Cart\Services;

use App\Modules\Cart\DTO\CartGroup;
use App\Modules\Cart\DTO\CartLine;
use App\Modules\Products\Models\Product;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Support\Facades\Session;

/**
 * Session-backed shopping cart. Guests and signed-in users alike get a cart
 * (guest checkout is supported in Phase 10). The cart's job in Phase 9 is to
 * hold items and split them into per-vendor / per-fulfilment-track groups,
 * each of which carries its own delivery estimate and COD eligibility derived
 * from the fulfilment matrix (BUSINESS_MODEL.md §3).
 */
class CartService
{
    private const SESSION_KEY = 'cart.items';

    public function __construct(private readonly SettingsService $settings) {}

    // ─── Mutations ──────────────────────────────────────────────────────────────

    public function add(string $productId, int $quantity = 1): void
    {
        $items = $this->rawItems();
        $items[$productId] = ($items[$productId] ?? 0) + max(1, $quantity);
        $this->persist($items);
    }

    public function update(string $productId, int $quantity): void
    {
        $items = $this->rawItems();

        if ($quantity <= 0) {
            unset($items[$productId]);
        } else {
            $items[$productId] = $quantity;
        }

        $this->persist($items);
    }

    public function remove(string $productId): void
    {
        $items = $this->rawItems();
        unset($items[$productId]);
        $this->persist($items);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    // ─── Reads ──────────────────────────────────────────────────────────────────

    /**
     * @return array<string, int>  productId => quantity
     */
    public function rawItems(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    public function isEmpty(): bool
    {
        return $this->rawItems() === [];
    }

    /**
     * Total number of units across the whole cart.
     */
    public function count(): int
    {
        return array_sum($this->rawItems());
    }

    public function subtotal(): float
    {
        return array_sum(array_map(fn (CartGroup $g) => $g->subtotal(), $this->groups()));
    }

    /**
     * Grand total including each group's delivery estimate.
     * (Coupons/discounts are deferred to Phase 20.)
     */
    public function total(): float
    {
        return array_sum(array_map(fn (CartGroup $g) => $g->total(), $this->groups()));
    }

    /**
     * Split the cart into per-vendor, per-fulfilment-track groups.
     *
     * @return CartGroup[]
     */
    public function groups(): array
    {
        $items = $this->rawItems();

        if ($items === []) {
            return [];
        }

        $products = Product::query()
            ->with(['vendor', 'category'])
            ->whereIn('id', array_keys($items))
            ->get()
            ->keyBy('id');

        // Bucket lines by vendor + fulfilment track.
        $buckets = [];
        foreach ($items as $productId => $quantity) {
            $product = $products->get($productId);
            if ($product === null) {
                continue; // product removed/unpublished since it was added
            }

            $track = $product->fulfilment_type ?? 'vendor';
            $key   = $product->vendor_id . '|' . $track;

            $buckets[$key]['track']    = $track;
            $buckets[$key]['lines'][]  = new CartLine($product, $quantity);
        }

        $fbsEnabled = $this->settings->getBool('cod.fbs_enabled');
        $vfEnabled  = $this->settings->getBool('cod.vf_enabled');
        $fbsFee     = $this->settings->getDecimal('delivery.fbs_default_fee');

        $groups = [];
        foreach ($buckets as $bucket) {
            /** @var CartLine[] $lines */
            $lines  = $bucket['lines'];
            $track  = $bucket['track'];
            $vendor = $lines[0]->product->vendor;

            $allCodAllowed = array_reduce(
                $lines,
                fn (bool $carry, CartLine $l) => $carry && (bool) $l->product->cod_allowed,
                true
            );

            // FBS COD: rider collects for the platform — no vendor wallet risk.
            $fbsCod = $fbsEnabled && $allCodAllowed;
            // VF COD: vendor collects cash — gated on wallet standing (cod_eligible).
            $vfCod  = $vfEnabled && $allCodAllowed && (bool) ($vendor?->cod_eligible);

            $codAvailable = match ($track) {
                'fbs'    => $fbsCod,
                'vendor' => $vfCod,
                default  => $fbsCod || $vfCod, // 'both'
            };

            [$deliveryFee, $deliveryLabel] = match ($track) {
                'vendor' => [null, 'Vendor-arranged delivery'],
                'fbs'    => [$fbsFee, 'Fulfilled by Salma'],
                default  => [$fbsFee, 'Fulfilled by Salma (or vendor delivery)'],
            };

            $groups[] = new CartGroup(
                vendorId: $vendor?->id ?? '',
                vendorName: $vendor?->name ?? 'Unknown vendor',
                track: $track,
                lines: $lines,
                deliveryFee: $deliveryFee,
                deliveryLabel: $deliveryLabel,
                codAvailable: $codAvailable,
                vendorCodEligible: (bool) ($vendor?->cod_eligible),
            );
        }

        return $groups;
    }

    // ─── Internals ──────────────────────────────────────────────────────────────

    /**
     * @param array<string, int> $items
     */
    private function persist(array $items): void
    {
        if ($items === []) {
            Session::forget(self::SESSION_KEY);
            return;
        }

        Session::put(self::SESSION_KEY, $items);
    }
}
