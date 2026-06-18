<?php

namespace Tests\Feature\Search;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZeroResultsRfqTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_products_zero_results_renders_rfq_cta(): void
    {
        $this->get(route('products.index', ['q' => 'zzz-no-such-product']))
            ->assertOk()
            ->assertSee('Request it')
            ->assertSee('requests/new'); // RFQ entry-point link is present
    }

    public function test_vehicles_zero_results_renders_rfq_cta(): void
    {
        $this->get(route('vehicles.index', ['search' => 'zzz-no-such-vehicle']))
            ->assertOk()
            ->assertSee('Request it');
    }

    public function test_rfq_create_stub_renders_with_prefill(): void
    {
        $this->get(route('rfq.create', ['q' => 'alternator', 'for' => 'products']))
            ->assertOk()
            ->assertSee("Can't find", false) // literal apostrophe, not HTML-escaped
            ->assertSee('alternator');
    }
}
