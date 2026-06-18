<?php

namespace Tests\Feature\Concierge;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Concierge\Models\ConciergeRequest;
use App\Modules\Concierge\Services\ConciergeService;
use App\Modules\Payments\Services\PesepayClient;
use App\Modules\Settings\Services\SettingsService;
use App\Modules\Wallet\Services\WalletService;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConciergeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class); // concierge.fee_minimum=5, fee_percent=10
    }

    private function buyer(): User
    {
        /** @var User $u */
        $u = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $u->assignRole('customer');
        return $u;
    }

    private function admin(): User
    {
        /** @var User $u */
        $u = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $u->assignRole('admin');
        return $u;
    }

    private function request(User $buyer): ConciergeRequest
    {
        return ConciergeRequest::create([
            'buyer_user_id' => $buyer->id, 'part_description' => 'Rare gearbox', 'location' => 'Harare', 'status' => 'new',
        ]);
    }

    // ─── Fee from settings ──────────────────────────────────────────────────────

    public function test_service_fee_is_max_of_minimum_and_percent(): void
    {
        $service = app(ConciergeService::class);

        $this->assertSame(5.0, $service->feeFor(10));   // 10% of 10 = 1 → min 5 wins
        $this->assertSame(20.0, $service->feeFor(200));  // 10% of 200 = 20 wins

        app(SettingsService::class)->set('concierge.fee_percent', 15);
        $this->assertSame(30.0, $service->feeFor(200));  // settings-driven
    }

    public function test_quote_computes_total_from_settings(): void
    {
        $request = $this->request($this->buyer());

        app(ConciergeService::class)->quote($request, partValue: 200, deliveryFee: 10, sourcedVendorId: null);

        $request->refresh();
        $this->assertSame('quoted', $request->status);
        $this->assertSame(20.0, (float) $request->service_fee);   // 10% of 200
        $this->assertSame(230.0, (float) $request->total);        // 200 + 20 + 10
    }

    // ─── End-to-end admin workflow ──────────────────────────────────────────────

    public function test_admin_can_run_a_request_end_to_end(): void
    {
        $admin   = $this->admin();
        $vendor  = Vendor::create(['name' => 'Src', 'slug' => 'src-' . Str::random(5), 'contact_email' => 's@x.com', 'status' => 'approved']);
        $request = $this->request($this->buyer());

        $this->actingAs($admin)->post(route('admin.concierge.transition', $request), ['to' => 'sourcing'])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.concierge.quote', $request), [
            'part_value' => 200, 'delivery_fee' => 10, 'sourced_vendor_id' => $vendor->id,
        ])->assertRedirect();
        $this->assertSame('quoted', $request->fresh()->status);

        // Buyer pays (simulate gateway success straight on the model).
        $request->update(['payment_status' => 'paid', 'status' => 'paid', 'paid_at' => now()]);

        $this->actingAs($admin)->post(route('admin.concierge.transition', $request), ['to' => 'fulfilling'])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.concierge.transition', $request), ['to' => 'delivered'])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.concierge.transition', $request), ['to' => 'closed'])->assertRedirect();

        $this->assertSame('closed', $request->fresh()->status);

        // On-platform source settled like an FBS order: vendor credited net of commission.
        // part 200, vendor default commission 10% → net 180.
        $this->assertSame(180.0, (float) app(WalletService::class)->walletFor($vendor)->cached_balance);
        $this->assertNotNull($request->fresh()->settled_at);
    }

    public function test_off_platform_request_does_not_credit_any_wallet(): void
    {
        $request = $this->request($this->buyer());
        app(ConciergeService::class)->quote($request, 200, 10, null); // no vendor
        $request->update(['status' => 'delivered', 'payment_status' => 'paid']);

        app(ConciergeService::class)->settle($request->fresh());

        $this->assertNull($request->fresh()->settled_at);
    }

    public function test_illegal_transition_is_rejected(): void
    {
        $request = $this->request($this->buyer());

        $this->actingAs($this->admin())
            ->post(route('admin.concierge.transition', $request), ['to' => 'delivered'])
            ->assertSessionHasErrors('concierge');

        $this->assertSame('new', $request->fresh()->status);
    }

    // ─── Payment webhook ────────────────────────────────────────────────────────

    public function test_payment_webhook_marks_request_paid(): void
    {
        $request = $this->request($this->buyer());
        app(ConciergeService::class)->quote($request, 200, 10, null);
        $request->update(['gateway_ref' => 'CONREF1']);

        $this->postJson(route('payments.webhook'), [
            'payload' => app(PesepayClient::class)->encrypt(['referenceNumber' => 'CONREF1', 'transactionStatus' => 'SUCCESS']),
        ])->assertOk();

        $this->assertSame('paid', $request->fresh()->payment_status);
        $this->assertSame('paid', $request->fresh()->status);
    }
}
