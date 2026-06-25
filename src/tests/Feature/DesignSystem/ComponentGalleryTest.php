<?php

namespace Tests\Feature\DesignSystem;

use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComponentGalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
    }

    public function test_component_gallery_renders_every_base_component(): void
    {
        $this->get('/_dev/components')
            ->assertOk()
            ->assertSee('Component gallery')
            // a sampling across the component families confirms each compiled
            ->assertSee('WhatsApp')                 // button variant
            ->assertSee('Featured')                 // badge preset
            ->assertSee('Profile views')            // stat-tile
            ->assertSee('Confirm publish')          // modal
            ->assertSee('No listings yet')          // empty state
            ->assertSee('role="switch"', false)     // toggle a11y
            ->assertSee('aria-modal="true"', false) // modal/drawer a11y
            ->assertSee('aria-label="Breadcrumb"', false);
    }
}
