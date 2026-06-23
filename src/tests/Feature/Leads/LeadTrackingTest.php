<?php

namespace Tests\Feature\Leads;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Leads\Models\Lead;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * D6: every buyer→seller contact (guest or logged-in) creates a tracked lead;
 * sellers see their own leads scoped, admins see all, and other contact surfaces
 * (RFQ) also record leads.
 */
class LeadTrackingTest extends TestCase
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

    private function seller(): User
    {
        $u = User::factory()->create(['role' => 'private_seller', 'status' => 'active', 'contact_phone' => '+263771234567', 'email_verified_at' => now(), 'force_password_change' => false]);
        $u->assignRole('private_seller');

        return $u;
    }

    private function vehicle(User $seller): Vehicle
    {
        return Vehicle::create([
            'user_id' => $seller->id, 'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2021, 'body_type' => 'pickup', 'transmission' => 'manual', 'fuel_type' => 'diesel',
            'mileage' => 1000, 'color' => 'white', 'condition' => 'used', 'price_usd' => 20000, 'status' => 'active',
        ]);
    }

    public function test_guest_contact_creates_a_lead_and_reveals_phone(): void
    {
        $seller = $this->seller();
        $vehicle = $this->vehicle($seller);

        $this->postJson(route('vehicles.contact', $vehicle), ['type' => 'contact_reveal'])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('contact.phone', '+263771234567');

        $this->assertDatabaseHas('leads', [
            'type' => 'contact_reveal', 'seller_user_id' => $seller->id,
            'buyer_user_id' => null, // guest
            'subject_id' => $vehicle->id,
        ]);
    }

    public function test_logged_in_buyer_contact_records_buyer(): void
    {
        $seller = $this->seller();
        $vehicle = $this->vehicle($seller);
        $buyer = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $buyer->assignRole('customer');

        $this->actingAs($buyer)->postJson(route('vehicles.contact', $vehicle), ['type' => 'contact_reveal'])->assertOk();

        $this->assertDatabaseHas('leads', [
            'seller_user_id' => $seller->id, 'buyer_user_id' => $buyer->id, 'subject_id' => $vehicle->id,
        ]);
    }

    public function test_seller_sees_only_their_own_leads(): void
    {
        $sellerA = $this->seller();
        $sellerB = $this->seller();
        Lead::create(['type' => 'contact_reveal', 'seller_user_id' => $sellerA->id, 'message' => 'A-lead-msg']);
        Lead::create(['type' => 'contact_reveal', 'seller_user_id' => $sellerB->id, 'message' => 'B-lead-msg']);

        $this->actingAs($sellerA)->get(route('seller.leads.index'))
            ->assertOk()
            ->assertSee('A-lead-msg')
            ->assertDontSee('B-lead-msg');
    }

    public function test_seller_can_update_lead_status(): void
    {
        $seller = $this->seller();
        $lead = Lead::create(['type' => 'contact_reveal', 'seller_user_id' => $seller->id, 'status' => 'new']);

        $this->actingAs($seller)->put(route('seller.leads.update', $lead), ['status' => 'converted'])->assertRedirect();
        $this->assertSame('converted', $lead->fresh()->status);
    }

    public function test_seller_cannot_update_another_sellers_lead(): void
    {
        $other = $this->seller();
        $lead = Lead::create(['type' => 'contact_reveal', 'seller_user_id' => $other->id, 'status' => 'new']);

        $this->actingAs($this->seller())->put(route('seller.leads.update', $lead), ['status' => 'converted'])->assertForbidden();
    }

    public function test_admin_sees_all_leads_with_funnel(): void
    {
        Lead::create(['type' => 'contact_reveal', 'seller_user_id' => $this->seller()->id, 'status' => 'converted']);
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $admin->assignRole('super_admin');

        $this->actingAs($admin)->get(route('admin.leads.index'))
            ->assertOk()
            ->assertSee('Total leads')
            ->assertSee('Converted');
    }

    public function test_rfq_submission_records_a_lead(): void
    {
        $buyer = User::factory()->create(['role' => 'customer', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $buyer->assignRole('customer');

        $this->actingAs($buyer)->post(route('rfq.store'), [
            'part_description' => 'Need a Toyota Hilux clutch kit',
            'location' => 'Harare',
        ])->assertRedirect();

        $this->assertDatabaseHas('leads', ['type' => 'rfq', 'buyer_user_id' => $buyer->id]);
    }
}
