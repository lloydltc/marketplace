<?php

namespace Tests\Unit\Products;

use App\Modules\Products\Models\Product;
use App\Modules\Products\Repositories\ProductRepositoryInterface;
use App\Modules\Products\Services\InventoryService;
use PHPUnit\Framework\TestCase;

class InventoryServiceTest extends TestCase
{
    private function product(int $quantity = 10, string $status = 'active'): Product
    {
        $p           = new Product();
        $p->id       = 'test-uuid';
        $p->quantity = $quantity;
        $p->status   = $status;

        return $p;
    }

    public function test_is_low_stock_returns_true_when_quantity_at_threshold(): void
    {
        $repo    = $this->createMock(ProductRepositoryInterface::class);
        $service = new InventoryService($repo);

        $this->assertTrue($service->isLowStock($this->product(quantity: 5), 5));
    }

    public function test_is_low_stock_returns_true_when_quantity_below_threshold(): void
    {
        $repo    = $this->createMock(ProductRepositoryInterface::class);
        $service = new InventoryService($repo);

        $this->assertTrue($service->isLowStock($this->product(quantity: 2), 5));
    }

    public function test_is_low_stock_returns_false_when_quantity_above_threshold(): void
    {
        $repo    = $this->createMock(ProductRepositoryInterface::class);
        $service = new InventoryService($repo);

        $this->assertFalse($service->isLowStock($this->product(quantity: 10), 5));
    }

    public function test_is_low_stock_returns_false_when_quantity_is_zero(): void
    {
        // Zero stock is "out of stock", not "low stock"
        $repo    = $this->createMock(ProductRepositoryInterface::class);
        $service = new InventoryService($repo);

        $this->assertFalse($service->isLowStock($this->product(quantity: 0), 5));
    }
}
