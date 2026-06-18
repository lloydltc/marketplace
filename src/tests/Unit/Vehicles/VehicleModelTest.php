<?php

namespace Tests\Unit\Vehicles;

use App\Modules\Vehicles\Models\Vehicle;
use PHPUnit\Framework\TestCase;

class VehicleModelTest extends TestCase
{
    private function vehicle(string $status = 'pending', ?string $vendorId = null, ?string $userId = null): Vehicle
    {
        $v            = new Vehicle();
        $v->status    = $status;
        $v->vendor_id = $vendorId;
        $v->user_id   = $userId;

        return $v;
    }

    public function test_is_active_returns_true_for_active_status(): void
    {
        $this->assertTrue($this->vehicle(status: 'active')->isActive());
    }

    public function test_is_active_returns_false_for_pending(): void
    {
        $this->assertFalse($this->vehicle(status: 'pending')->isActive());
    }

    public function test_is_pending_returns_true(): void
    {
        $this->assertTrue($this->vehicle(status: 'pending')->isPending());
    }

    public function test_is_rejected_returns_true(): void
    {
        $this->assertTrue($this->vehicle(status: 'rejected')->isRejected());
    }

    public function test_is_inactive_returns_true(): void
    {
        $this->assertTrue($this->vehicle(status: 'inactive')->isInactive());
    }

    public function test_can_be_edited_for_pending(): void
    {
        $this->assertTrue($this->vehicle(status: 'pending')->canBeEdited());
    }

    public function test_can_be_edited_for_rejected(): void
    {
        $this->assertTrue($this->vehicle(status: 'rejected')->canBeEdited());
    }

    public function test_can_be_edited_for_inactive(): void
    {
        $this->assertTrue($this->vehicle(status: 'inactive')->canBeEdited());
    }

    public function test_cannot_be_edited_when_active(): void
    {
        $this->assertFalse($this->vehicle(status: 'active')->canBeEdited());
    }

    public function test_is_listed_by_vendor_when_vendor_id_set(): void
    {
        $this->assertTrue($this->vehicle(vendorId: 'some-uuid')->isListedByVendor());
    }

    public function test_is_listed_by_vendor_false_when_user_id_set(): void
    {
        $this->assertFalse($this->vehicle(userId: 'some-uuid')->isListedByVendor());
    }

    public function test_is_listed_by_private_seller_when_user_id_set(): void
    {
        $this->assertTrue($this->vehicle(userId: 'some-uuid')->isListedByPrivateSeller());
    }
}
