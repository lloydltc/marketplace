<?php

namespace Tests\Feature\Rfq;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Orders\Models\Order;
use App\Modules\Rfq\Models\PartRequest;
use App\Modules\Rfq\Models\Quote;
use App\Modules\Wallet\Services\WalletService;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RfqLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
    }

    private function buyer(): User
    {
        /** @var User $u */
        $u = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $u->assignRole('customer');
        return $u;
    }

    private function vendorWithAdmin(): array
    {
        $vendor = Vendor::create([
            'name' => 'PartsCo', 'slug' => 'partsco-' . Str::random(5), 'contact_email' => 'p@x.com', 'status' => 'approved',
        ]);
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'vendor_admin', 'email_verified_at' => now()]);
        $admin->assignRole('vendor_admin');
        $admin->vendors()->attach($vendor->id, ['vendor_role' => 'admin', 'invited_at' => now(), 'joined_at' => now()]);
        return [$vendor, $admin];
    }

    public function test_full_lifecycle_converts_quote_to_a_settleable_order(): void
    {
        $buyer            = $this->buyer();
        [$vendor, $admin] = $this->vendorWithAdmin();

        // 1. Buyer posts a request.
        $this->actingAs($buyer)->post(route('rfq.store'), [
            'part_description' => 'Front brake pads for Hilux',
            'location'         => 'Harare',
        ])->assertRedirect();

        $request = PartRequest::firstOrFail();
        $this->assertSame('open', $request->status);

        // 2. Vendor quotes.
        $this->actingAs($admin)->post(route('vendor.requests.quote', $request), [
            'price' => 120, 'condition' => 'new', 'delivery_estimate' => '2-3 days',
        ])->assertRedirect();

        $quote = Quote::firstOrFail();
        $this->assertSame('quoted', $request->fresh()->status);

        // 3. Buyer accepts → converts to an order.
        $this->actingAs($buyer)->post(route('rfq.accept', [$request, $quote]), [
            'full_name' => 'Buyer One', 'email' => 'b@x.com', 'phone' => '0772000000',
            'address' => '1 Main St', 'city' => 'Harare',
        ])->assertRedirect(route('checkout.complete'));

        $request->refresh();
        $this->assertSame('converted', $request->status);
        $this->assertNotNull($request->converted_order_id);

        $order = Order::findOrFail($request->converted_order_id);
        $this->assertSame('pending_payment', $order->status);
        $this->assertSame('vendor', $order->fulfilment_track);
        $this->assertSame(120.0, (float) $order->subtotal);
        $this->assertSame(12.0, (float) $order->commission_amount);  // vendor default 10%
        $this->assertSame(108.0, (float) $order->net_to_vendor);

        // 4. The order is indistinguishable to the settlement engine: walking it
        //    to completed credits the vendor wallet exactly like a normal order.
        $order->markPaid();
        $order->transitionTo('processing');
        $order->transitionTo('vendor_shipping');
        $order->transitionTo('delivered');
        $order->transitionTo('completed');

        $this->assertSame(108.0, (float) app(WalletService::class)->walletFor($vendor)->cached_balance);
    }

    public function test_accepting_rejects_the_other_quotes(): void
    {
        $buyer            = $this->buyer();
        [$vendor, $admin] = $this->vendorWithAdmin();
        [, $admin2]       = $this->vendorWithAdmin();

        $this->actingAs($buyer)->post(route('rfq.store'), ['part_description' => 'Alternator', 'location' => 'Harare']);
        $request = PartRequest::firstOrFail();

        $this->actingAs($admin)->post(route('vendor.requests.quote', $request), ['price' => 100, 'condition' => 'used']);
        $this->actingAs($admin2)->post(route('vendor.requests.quote', $request), ['price' => 90, 'condition' => 'used']);

        $accepted = Quote::where('price', 100)->firstOrFail();
        $this->actingAs($buyer)->post(route('rfq.accept', [$request, $accepted]), [
            'full_name' => 'B', 'email' => 'b@x.com', 'phone' => '1', 'address' => 'a', 'city' => 'Harare',
        ]);

        $this->assertSame('accepted', $accepted->fresh()->status);
        $this->assertSame('rejected', Quote::where('price', 90)->first()->status);
    }

    public function test_vendor_cannot_quote_twice(): void
    {
        $buyer            = $this->buyer();
        [, $admin]        = $this->vendorWithAdmin();

        $this->actingAs($buyer)->post(route('rfq.store'), ['part_description' => 'Clutch', 'location' => 'Harare']);
        $request = PartRequest::firstOrFail();

        $this->actingAs($admin)->post(route('vendor.requests.quote', $request), ['price' => 50, 'condition' => 'new']);
        $this->actingAs($admin)->post(route('vendor.requests.quote', $request), ['price' => 60, 'condition' => 'new'])
            ->assertSessionHasErrors('quote');

        $this->assertSame(1, Quote::where('part_request_id', $request->id)->count());
    }

    public function test_buyer_cannot_view_another_buyers_request(): void
    {
        $owner = $this->buyer();
        $other = $this->buyer();
        $this->actingAs($owner)->post(route('rfq.store'), ['part_description' => 'Mirror', 'location' => 'Harare']);
        $request = PartRequest::firstOrFail();

        $this->actingAs($other)->get(route('rfq.show', $request))->assertForbidden();
    }
}
