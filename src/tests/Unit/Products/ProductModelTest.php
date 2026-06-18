<?php

namespace Tests\Unit\Products;

use App\Modules\Products\Models\Product;
use PHPUnit\Framework\TestCase;

class ProductModelTest extends TestCase
{
    private function product(
        string $status   = 'pending',
        int    $quantity = 10
    ): Product {
        $p           = new Product();
        $p->status   = $status;
        $p->quantity = $quantity;

        return $p;
    }

    public function test_is_active_returns_true_for_active_status(): void
    {
        $this->assertTrue($this->product(status: 'active')->isActive());
    }

    public function test_is_active_returns_false_for_pending_status(): void
    {
        $this->assertFalse($this->product(status: 'pending')->isActive());
    }

    public function test_is_pending_returns_true(): void
    {
        $this->assertTrue($this->product(status: 'pending')->isPending());
    }

    public function test_is_rejected_returns_true(): void
    {
        $this->assertTrue($this->product(status: 'rejected')->isRejected());
    }

    public function test_is_in_stock_returns_true_when_quantity_above_zero(): void
    {
        $this->assertTrue($this->product(quantity: 5)->isInStock());
    }

    public function test_is_in_stock_returns_false_when_quantity_is_zero(): void
    {
        $this->assertFalse($this->product(quantity: 0)->isInStock());
    }

    public function test_can_be_edited_by_vendor_for_pending(): void
    {
        $this->assertTrue($this->product(status: 'pending')->canBeEditedByVendor());
    }

    public function test_can_be_edited_by_vendor_for_rejected(): void
    {
        $this->assertTrue($this->product(status: 'rejected')->canBeEditedByVendor());
    }

    public function test_can_be_edited_by_vendor_for_inactive(): void
    {
        $this->assertTrue($this->product(status: 'inactive')->canBeEditedByVendor());
    }

    public function test_cannot_be_edited_by_vendor_when_active(): void
    {
        $this->assertFalse($this->product(status: 'active')->canBeEditedByVendor());
    }
}
