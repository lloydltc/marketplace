<?php

namespace Tests\Feature\TradeIn;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\TradeIn\Models\TradeIn;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use App\Notifications\TradeInOfferAcceptedNotification;
use App\Notifications\TradeInSubmittedNotification;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * TI2: dealer bidding — notify verified dealers, bid portal, buyer compare/accept.
 */
class DealerBiddingTest extends TestCase
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

    private function buyer(): User
    {
        $u = User::factory()->create(['role' => 'customer', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $u->assignRole('customer');

        return $u;
    }

    private function dealer(): array
    {
        $vendor = Vendor::create(['name' => 'D ' . Str::random(4), 'slug' => 'd-' . Str::random(6), 'contact_email' => Str::random(5) . '@x.com', 'status' => 'approved']);
        $admin = User::factory()->create(['role' => 'vendor_admin', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $admin->assignRole('vendor_admin');
        $vendor->users()->attach($admin->id, ['vendor_role' => 'admin', 'joined_at' => now()]);

        return [$vendor, $admin];
    }

    private function tradeIn(User $buyer): TradeIn
    {
        return TradeIn::create([
            'user_id' => $buyer->id, 'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2018, 'mileage' => 85000, 'condition' => 'good', 'status' => 'valued',
            'estimate_low_minor' => 1800000, 'estimate_high_minor' => 2200000, 'comparables_count' => 3,
        ]);
    }

    public function test_submission_notifies_approved_dealers(): void
    {
        Notification::fake();
        [$vendor, $admin] = $this->dealer();

        $this->actingAs($this->buyer())->post(route('trade-ins.store'), [
            'make_id' => $this->make->id, 'model_id' => $this->model->id, 'year' => 2018, 'mileage' => 85000, 'condition' => 'good',
        ])->assertRedirect();

        Notification::assertSentTo($admin, TradeInSubmittedNotification::class);
    }

    public function test_dealer_can_bid_and_update(): void
    {
        $buyer = $this->buyer();
        [$vendor, $admin] = $this->dealer();
        $tradeIn = $this->tradeIn($buyer);

        $this->actingAs($admin)->post(route('vendor.trade-ins.bid', $tradeIn), ['amount' => 19000, 'notes' => 'Firm'])->assertRedirect();
        $this->assertDatabaseHas('trade_in_offers', ['trade_in_id' => $tradeIn->id, 'vendor_id' => $vendor->id, 'amount_minor' => 1900000]);
        $this->assertSame('bidding', $tradeIn->fresh()->status);

        // Second bid updates (unique per vendor), not duplicates.
        $this->actingAs($admin)->post(route('vendor.trade-ins.bid', $tradeIn), ['amount' => 19500]);
        $this->assertSame(1, $tradeIn->offers()->count());
        $this->assertSame(1950000, $tradeIn->offers()->first()->amount_minor);
    }

    public function test_buyer_accepts_offer_declines_rest_and_notifies_dealer(): void
    {
        Notification::fake();
        $buyer = $this->buyer();
        [$v1, $a1] = $this->dealer();
        [$v2, $a2] = $this->dealer();
        $tradeIn = $this->tradeIn($buyer);
        $win = $tradeIn->offers()->create(['vendor_id' => $v1->id, 'amount_minor' => 2000000, 'currency' => 'USD', 'status' => 'offered']);
        $lose = $tradeIn->offers()->create(['vendor_id' => $v2->id, 'amount_minor' => 1800000, 'currency' => 'USD', 'status' => 'offered']);

        $this->actingAs($buyer)->post(route('trade-ins.offers.accept', [$tradeIn, $win]))->assertRedirect();

        $this->assertSame('accepted', $win->fresh()->status);
        $this->assertSame('declined', $lose->fresh()->status);
        $this->assertSame('accepted', $tradeIn->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'trade_in.offer.accepted', 'target_id' => $tradeIn->id]);
        Notification::assertSentTo($a1, TradeInOfferAcceptedNotification::class);
    }

    public function test_buyer_cannot_accept_offer_on_another_trade_in(): void
    {
        $buyer = $this->buyer();
        $intruder = $this->buyer();
        [$v1] = $this->dealer();
        $tradeIn = $this->tradeIn($buyer);
        $offer = $tradeIn->offers()->create(['vendor_id' => $v1->id, 'amount_minor' => 2000000, 'currency' => 'USD', 'status' => 'offered']);

        $this->actingAs($intruder)->post(route('trade-ins.offers.accept', [$tradeIn, $offer]))->assertForbidden();
    }

    public function test_admin_queue_lists_trade_ins(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $admin->assignRole('admin');
        $this->tradeIn($this->buyer());

        $this->actingAs($admin)->get(route('admin.trade-ins.index'))->assertOk()->assertSee('2018 Toyota Hilux');
    }
}
