<?php

namespace Tests\Unit\Vendor;

use App\Models\Vendor;
use App\Modules\Vendors\Repositories\VendorRepositoryInterface;
use App\Modules\Vendors\Services\VendorTierService;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\TestCase;

class VendorTierTest extends TestCase
{
    private VendorTierService $tierService;

    protected function setUp(): void
    {
        parent::setUp();

        $repo = Mockery::mock(VendorRepositoryInterface::class);
        $repo->shouldReceive('update')->andReturnUsing(
            function (Vendor $v, array $data) {
                foreach ($data as $k => $val) {
                    $v->{$k} = $val;
                }
                return $v;
            }
        );

        $this->tierService = new VendorTierService($repo);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function vendor(string $tier): Vendor
    {
        $v = new Vendor();
        $v->tier = $tier;
        $v->commission_rate = 10.0;
        return $v;
    }

    public function test_bronze_listing_limit_is_10(): void
    {
        $this->assertEquals(10, $this->tierService->getListingLimit($this->vendor('bronze')));
    }

    public function test_silver_listing_limit_is_100(): void
    {
        $this->assertEquals(100, $this->tierService->getListingLimit($this->vendor('silver')));
    }

    public function test_gold_listing_limit_is_unlimited(): void
    {
        $this->assertEquals(PHP_INT_MAX, $this->tierService->getListingLimit($this->vendor('gold')));
    }

    public function test_commission_rates_are_correct(): void
    {
        $this->assertEquals(10.00, $this->tierService->getCommissionRate($this->vendor('bronze')));
        $this->assertEquals(8.00,  $this->tierService->getCommissionRate($this->vendor('silver')));
        $this->assertEquals(5.00,  $this->tierService->getCommissionRate($this->vendor('gold')));
        $this->assertEquals(3.00,  $this->tierService->getCommissionRate($this->vendor('platinum')));
    }

    public function test_cannot_upgrade_to_same_tier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->tierService->upgrade($this->vendor('silver'), 'silver');
    }

    public function test_cannot_upgrade_to_lower_tier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->tierService->upgrade($this->vendor('gold'), 'bronze');
    }

    public function test_cannot_downgrade_to_higher_tier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->tierService->downgrade($this->vendor('bronze'), 'platinum');
    }

    public function test_upgrade_updates_commission_rate(): void
    {
        $vendor = $this->vendor('bronze');
        $this->tierService->upgrade($vendor, 'silver');
        $this->assertEquals(8.00, $vendor->commission_rate);
    }
}
