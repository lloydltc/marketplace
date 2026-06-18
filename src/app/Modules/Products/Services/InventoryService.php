<?php

namespace App\Modules\Products\Services;

use App\Modules\Products\Events\ProductStockDepletedEvent;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Repositories\ProductRepositoryInterface;
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
}
