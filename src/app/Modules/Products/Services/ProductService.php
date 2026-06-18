<?php

namespace App\Modules\Products\Services;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Products\Events\ProductApprovedEvent;
use App\Modules\Products\Events\ProductCreatedEvent;
use App\Modules\Products\Events\ProductRejectedEvent;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Repositories\ProductRepositoryInterface;
use App\Modules\Verification\Services\TierService;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Support\Facades\Log;

class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
        private readonly TierService $tierService,
        private readonly WalletService $wallet,
    ) {}

    public function createForVendor(Vendor $vendor, array $data): Product
    {
        // Wallet must be in good standing (above the floor) to add listings.
        $this->wallet->assertCanList($vendor);
        $this->tierService->assertCanCreateProductForVendor($vendor);

        $data['vendor_id'] = $vendor->id;
        $data['status']    = 'pending';

        $product = $this->repository->create($data);

        Log::info('Product created', ['product_id' => $product->id, 'vendor_id' => $vendor->id]);

        event(new ProductCreatedEvent($product));

        return $product;
    }

    public function update(Product $product, array $data): Product
    {
        // Resubmitting a rejected product resets it to pending for re-review
        if ($product->isRejected()) {
            $data['status'] = 'pending';
        }

        return $this->repository->update($product, $data);
    }

    public function approve(Product $product, User $admin): void
    {
        $this->repository->update($product, ['status' => 'active']);

        Log::info('Product approved', ['product_id' => $product->id, 'by' => $admin->id]);

        event(new ProductApprovedEvent($product->refresh()));
    }

    public function reject(Product $product, User $admin, string $reason): void
    {
        $this->repository->update($product, ['status' => 'rejected']);

        Log::info('Product rejected', ['product_id' => $product->id, 'by' => $admin->id]);

        event(new ProductRejectedEvent($product->refresh(), $reason));
    }

    public function deactivate(Product $product): void
    {
        $this->repository->update($product, ['status' => 'inactive']);
    }

    public function reactivate(Product $product): void
    {
        if ($product->isInStock()) {
            $this->repository->update($product, ['status' => 'active']);
        }
    }

    public function delete(Product $product): void
    {
        $this->repository->delete($product);
    }
}
