<?php

namespace Tests\Feature\Commerce;

use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Commerce\Services\CommissionRateResolver;
use App\Modules\Settings\Services\SettingsService;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionRateResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
    }

    private function resolver(): CommissionRateResolver
    {
        return app(CommissionRateResolver::class);
    }

    public function test_vendor_override_wins_first(): void
    {
        $vendor   = new Vendor(['commission_rate' => 7.5]);
        $category = new Category(['commission_override' => 4.0]);

        $this->assertSame(7.5, $this->resolver()->resolve($vendor, $category));
    }

    public function test_category_override_used_when_vendor_has_none(): void
    {
        $vendor   = new Vendor(['commission_rate' => null]);
        $category = new Category(['commission_override' => 4.0]);

        $this->assertSame(4.0, $this->resolver()->resolve($vendor, $category));
    }

    public function test_platform_default_used_when_no_overrides(): void
    {
        $vendor   = new Vendor(['commission_rate' => null]);
        $category = new Category(['commission_override' => null]);

        $this->assertSame(10.0, $this->resolver()->resolve($vendor, $category));
    }

    public function test_platform_default_reflects_settings_changes(): void
    {
        app(SettingsService::class)->set('commission.default_rate', 8.0);

        $this->assertSame(8.0, $this->resolver()->resolve());
    }

    public function test_resolves_with_no_arguments_to_platform_default(): void
    {
        $this->assertSame(10.0, $this->resolver()->resolve());
    }
}
