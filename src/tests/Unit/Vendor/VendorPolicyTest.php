<?php

namespace Tests\Unit\Vendor;

use App\Models\User;
use App\Models\Vendor;
use App\Policies\VendorPolicy;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\TestCase;

class VendorPolicyTest extends TestCase
{
    private VendorPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new VendorPolicy();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function user(string $role, bool $belongsToVendor = false): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->role = $role;
        $user->shouldReceive('isVendorAdmin')->andReturn($role === 'vendor_admin');
        $user->shouldReceive('isVendorWorker')->andReturn($role === 'vendor_worker');
        $user->shouldReceive('belongsToVendor')->andReturn($belongsToVendor);
        return $user;
    }

    private function vendor(): Vendor
    {
        $vendor     = new Vendor();
        $vendor->id = (string) Str::uuid();
        return $vendor;
    }

    public function test_anyone_can_view_vendor(): void
    {
        $this->assertTrue($this->policy->view($this->user('customer'), $this->vendor()));
        $this->assertTrue($this->policy->view($this->user('admin'), $this->vendor()));
    }

    public function test_vendor_admin_can_update_own_vendor(): void
    {
        $this->assertTrue($this->policy->update($this->user('vendor_admin', true), $this->vendor()));
    }

    public function test_vendor_admin_cannot_update_other_vendor(): void
    {
        $this->assertFalse($this->policy->update($this->user('vendor_admin', false), $this->vendor()));
    }

    public function test_customer_cannot_update_vendor(): void
    {
        $this->assertFalse($this->policy->update($this->user('customer', false), $this->vendor()));
    }

    public function test_admin_can_update_any_vendor(): void
    {
        $this->assertTrue($this->policy->update($this->user('admin'), $this->vendor()));
    }

    public function test_only_admin_can_approve(): void
    {
        $this->assertTrue($this->policy->approve($this->user('admin'), $this->vendor()));
        $this->assertTrue($this->policy->approve($this->user('super_admin'), $this->vendor()));
        $this->assertFalse($this->policy->approve($this->user('vendor_admin', true), $this->vendor()));
        $this->assertFalse($this->policy->approve($this->user('customer'), $this->vendor()));
    }

    public function test_only_admin_can_suspend(): void
    {
        $this->assertTrue($this->policy->suspend($this->user('admin'), $this->vendor()));
        $this->assertFalse($this->policy->suspend($this->user('vendor_admin', true), $this->vendor()));
    }

    public function test_vendor_admin_can_upload_documents_for_own_vendor(): void
    {
        $this->assertTrue($this->policy->uploadDocument($this->user('vendor_admin', true), $this->vendor()));
        $this->assertFalse($this->policy->uploadDocument($this->user('vendor_admin', false), $this->vendor()));
    }
}
