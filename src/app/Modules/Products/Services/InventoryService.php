<?php

namespace App\Modules\Products\Services;

use App\Models\User;
use App\Modules\Products\Events\ProductStockDepletedEvent;
use App\Modules\Products\Exceptions\InsufficientStockException;
use App\Modules\Products\Models\InventoryMovement;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Repositories\ProductRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository
    ) {}

    public function adjustQuantity(Product $product, int $delta): void
    {
        $newQty = max(0, $product->quantity + $delta);

        $this->repository->update($product, ['quantity' => $newQty]);

        $product->refresh();

        if ($product->quantity === 0 && $product->isActive()) {
            Log::warning('Product stock depleted', ['product_id' => $product->id]);
            event(new ProductStockDepletedEvent($product));
        }
    }

    public function setQuantity(Product $product, int $quantity): void
    {
        if ($quantity < 0) {
            $quantity = 0;
        }

        $wasOutOfStock = $product->quantity === 0;

        $this->repository->update($product, ['quantity' => $quantity]);

        $product->refresh();

        if ($product->quantity === 0 && $product->isActive()) {
            Log::warning('Product stock depleted', ['product_id' => $product->id]);
            event(new ProductStockDepletedEvent($product));
        }

        // Reactivate auto-deactivated product when stock returns
        if ($wasOutOfStock && $quantity > 0 && $product->isInactive()) {
            $this->repository->update($product, ['status' => 'active']);
        }
    }

    public function decrementForOrder(Product $product, int $units): void
    {
        $this->adjustQuantity($product, -$units);
    }

    public function isLowStock(Product $product, int $threshold = 5): bool
    {
        return $product->quantity > 0 && $product->quantity <= $threshold;
    }

    // ─── PM2: auditable stock movements (never negative; one movement per change) ──
    // These layer on top of the legacy adjust/set methods above. Reserve/release/
    // recordSale are wired into the order lifecycle in PM10.

    public function restock(Product $product, int $qty, ?string $reference = null, ?User $actor = null): int
    {
        return $this->applyMovement($product, 'restock', abs($qty), $reference, $actor);
    }

    public function reserve(Product $product, int $qty, ?string $reference = null, ?User $actor = null): int
    {
        return $this->applyMovement($product, 'reserve', -abs($qty), $reference, $actor);
    }

    public function release(Product $product, int $qty, ?string $reference = null, ?User $actor = null): int
    {
        return $this->applyMovement($product, 'release', abs($qty), $reference, $actor);
    }

    public function recordSale(Product $product, int $qty, ?string $reference = null, ?User $actor = null): int
    {
        return $this->applyMovement($product, 'sale', -abs($qty), $reference, $actor);
    }

    /** Set the absolute on-hand quantity (manual correction), recording the delta. */
    public function adjustTo(Product $product, int $newQty, ?string $reference = null, ?User $actor = null): int
    {
        if ($newQty < 0) {
            throw InsufficientStockException::for($product->title, 0, $newQty);
        }

        return DB::transaction(function () use ($product, $newQty, $reference, $actor) {
            $fresh = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();

            return $this->commitMovement($fresh, 'adjustment', $newQty - (int) $fresh->quantity, $newQty, $reference, $actor);
        });
    }

    private function applyMovement(Product $product, string $type, int $signedQty, ?string $reference, ?User $actor): int
    {
        return DB::transaction(function () use ($product, $type, $signedQty, $reference, $actor) {
            $fresh   = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $balance = (int) $fresh->quantity + $signedQty;

            if ($balance < 0) {
                throw InsufficientStockException::for($fresh->title, (int) $fresh->quantity, abs($signedQty));
            }

            return $this->commitMovement($fresh, $type, $signedQty, $balance, $reference, $actor);
        });
    }

    private function commitMovement(Product $product, string $type, int $signedQty, int $balance, ?string $reference, ?User $actor): int
    {
        $product->forceFill(['quantity' => $balance])->save();

        InventoryMovement::create([
            'product_id'    => $product->id,
            'type'          => $type,
            'qty'           => $signedQty,
            'balance_after' => $balance,
            'reference'     => $reference,
            'created_by'    => $actor?->id,
        ]);

        return $balance;
    }
}
