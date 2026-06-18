<?php

namespace App\Modules\Products\Listeners;

use App\Modules\Products\Events\ProductCreatedEvent;
use App\Modules\Products\Events\ProductApprovedEvent;
use App\Modules\Products\Repositories\ProductRepositoryInterface;
use Illuminate\Support\Facades\Log;

class AutoApproveOrQueueProduct
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository
    ) {}

    public function handle(ProductCreatedEvent $event): void
    {
        $product = $event->product->load('category');

        // Auto-approve products in categories without a commission override
        // (override signals a category that may need closer review).
        // Manual review categories keep status = pending for admin action.
        if ($product->category && $product->category->commission_override === null) {
            $this->repository->update($product, ['status' => 'active']);

            Log::info('Product auto-approved', ['product_id' => $product->id]);

            event(new ProductApprovedEvent($product->refresh()));
        }
    }
}
