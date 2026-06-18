<?php

namespace App\Modules\Products\Listeners;

use App\Modules\Products\Events\ProductStockDepletedEvent;
use App\Modules\Products\Repositories\ProductRepositoryInterface;
use Illuminate\Support\Facades\Log;

class DeactivateProductOnZeroStock
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository
    ) {}

    public function handle(ProductStockDepletedEvent $event): void
    {
        $product = $event->product;

        if ($product->isActive()) {
            $this->repository->update($product, ['status' => 'inactive']);

            Log::info('Product deactivated due to zero stock', ['product_id' => $product->id]);
        }
    }
}
