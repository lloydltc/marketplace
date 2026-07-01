<?php

namespace Tests\Feature\TradeIn;

use App\Models\User;
use App\Modules\TradeIn\Models\TradeIn;
use App\Modules\TradeIn\Services\ValuationService;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * TI1: comparable-listing valuation (deterministic, no AI) + buyer submission.
 */
class ValuationAndSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private VehicleMake $make;
    private VehicleModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
        $this->make = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota-' . Str::random(4), 'sort_order' => 0]);
        $this->model = VehicleModel::create(['make_id' => $this->make->id, 'name' => 'Hilux', 'slug' => 'hilux-' . Str::random(4)]);
    }

    private function comparable(int $year, float $priceUsd, int $mileage): void
    {
        $seller = User::factory()->create(['role' => 'private_seller', 'status' => 'active']);
        Vehicle::create([
            'user_id' => $seller->id, 'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => $year, 'body_type' => 'pickup', 'transmission' => 'manual', 'fuel_type' => 'diesel',
            'mileage' => $mileage, 'color' => 'white', 'condition' => 'used', 'price_usd' => $priceUsd,
            'vehicle_type' => 'vehicle', 'status' => 'active', 'expires_at' => now()->addDays(10),
        ]);
    }

    private function buyer(): User
    {
        $u = User::factory()->create(['role' => 'customer', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $u->assignRole('customer');

        return $u;
    }

    public function test_estimate_from_comparables_returns_a_range(): void
    {
        $this->comparable(2018, 20000, 80000);
        $this->comparable(2018, 22000, 90000);
        $this->comparable(2019, 24000, 70000);

        $est = app(ValuationService::class)->estimate($this->make->id, $this->model->id, 2018, 80000, 'good');

        $this->assertNotNull($est);
        $this->assertSame(3, $est['comparables']);
        $this->assertGreaterThan(0, $est['low_minor']);
        $this->assertLessThan($est['high_minor'], $est['low_minor']); // low < high
    }

    public function test_condition_lowers_estimate(): void
    {
        foreach ([[2018, 20000, 80000], [2018, 20000, 80000], [2018, 20000, 80000]] as [$y, $p, $m]) {
            $this->comparable($y, $p, $m);
        }
        $svc = app(ValuationService::class);

        $good = $svc->estimate($this->make->id, $this->model->id, 2018, 80000, 'good');
        $poor = $svc->estimate($this->make->id, $this->model->id, 2018, 80000, 'poor');

        $this->assertLessThan($good['base'], $poor['base']); // poor condition < good
    }

    public function test_insufficient_comparables_returns_null(): void
    {
        $this->comparable(2018, 20000, 80000); // only 1, below min

        $this->assertNull(app(ValuationService::class)->estimate($this->make->id, $this->model->id, 2018, 80000, 'good'));
    }

    public function test_buyer_can_submit_and_see_estimate(): void
    {
        $this->comparable(2018, 20000, 80000);
        $this->comparable(2018, 22000, 90000);
        $this->comparable(2019, 24000, 70000);
        $buyer = $this->buyer();

        $this->actingAs($buyer)->post(route('trade-ins.store'), [
            'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2018, 'mileage' => 85000, 'condition' => 'good',
        ])->assertRedirect();

        $tradeIn = TradeIn::where('user_id', $buyer->id)->first();
        $this->assertNotNull($tradeIn->estimate_low_minor);
        $this->assertSame('valued', $tradeIn->status);

        $this->actingAs($buyer)->get(route('trade-ins.show', $tradeIn))
            ->assertOk()->assertSee('estimate, not an offer', false)->assertSee('Estimated trade-in value');
    }

    public function test_submission_without_comparables_still_saved(): void
    {
        $buyer = $this->buyer();

        $this->actingAs($buyer)->post(route('trade-ins.store'), [
            'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2010, 'mileage' => 200000, 'condition' => 'fair',
        ])->assertRedirect();

        $tradeIn = TradeIn::where('user_id', $buyer->id)->first();
        $this->assertNull($tradeIn->estimate_low_minor); // honest: no fabricated estimate
        $this->assertFalse($tradeIn->hasEstimate());
    }
}
