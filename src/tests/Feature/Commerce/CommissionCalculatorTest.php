<?php

namespace Tests\Feature\Commerce;

use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Commerce\Services\CommissionCalculator;
use App\Modules\Settings\Services\SettingsService;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
    }

    private function calc(): CommissionCalculator
    {
        return app(CommissionCalculator::class);
    }

    public function test_vendor_override_wins(): void
    {
        $vendor = new Vendor(['commission_rate' => 8]);
        $cat    = new Category(['commission_override' => 4]);

        $r = $this->calc()->forLines($vendor, [['line_total' => 100, 'category' => $cat]]);

        $this->assertSame(8.0, $r['rate']);
        $this->assertSame(8.0, $r['amount']);
        $this->assertSame(92.0, $r['net']);
    }

    public function test_category_override_when_vendor_has_none(): void
    {
        $vendor = new Vendor(['commission_rate' => null]);
        $cat    = new Category(['commission_override' => 4]);

        $r = $this->calc()->forLines($vendor, [['line_total' => 100, 'category' => $cat]]);

        $this->assertSame(4.0, $r['rate']);
        $this->assertSame(4.0, $r['amount']);
    }

    public function test_platform_default_when_no_overrides(): void
    {
        $vendor = new Vendor(['commission_rate' => null]);
        $cat    = new Category(['commission_override' => null]);

        $r = $this->calc()->forLines($vendor, [['line_total' => 100, 'category' => $cat]]);

        $this->assertSame(10.0, $r['rate']);   // seeded platform default
        $this->assertSame(10.0, $r['amount']);
    }

    public function test_blended_rate_across_mixed_categories(): void
    {
        $vendor = new Vendor(['commission_rate' => null]);
        $cheap  = new Category(['commission_override' => 4]);
        $std    = new Category(['commission_override' => null]); // platform default 10

        $r = $this->calc()->forLines($vendor, [
            ['line_total' => 100, 'category' => $cheap], // 4
            ['line_total' => 100, 'category' => $std],   // 10
        ]);

        $this->assertSame(14.0, $r['amount']);
        $this->assertSame(186.0, $r['net']);
        $this->assertSame(7.0, $r['rate']); // 14 / 200
    }

    public function test_commission_is_on_subtotal_only(): void
    {
        // Delivery fee never enters the calculator — net is subtotal minus commission.
        $vendor = new Vendor(['commission_rate' => 10]);
        $r = $this->calc()->forLines($vendor, [['line_total' => 250, 'category' => null]]);

        $this->assertSame(250.0, $r['subtotal']);
        $this->assertSame(25.0, $r['amount']);
        $this->assertSame(225.0, $r['net']);
    }

    public function test_default_reads_from_settings(): void
    {
        app(SettingsService::class)->set('commission.default_rate', 12);

        $vendor = new Vendor(['commission_rate' => null]);
        $r = $this->calc()->forLines($vendor, [['line_total' => 100, 'category' => null]]);

        $this->assertSame(12.0, $r['amount']);
    }
}
