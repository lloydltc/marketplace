<?php

namespace Tests\Feature\Delivery;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Orders\Models\Order;
use App\Modules\Wallet\Services\WalletService;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VendorFulfilmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class); // delivery.vf_auto_complete_days = 7
    }

    private function buyer(): User
    {
        /** @var User $user */
        $user = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $user->assignRole('customer');
        return $user;
    }

    private function vfOrder(?string $buyerId, string $status = 'delivered'): Order
    {
        $vendor = Vendor::create([
            'name' => 'V', 'slug' => 'v-' . Str::random(5), 'contact_email' => 'v@x.com', 'status' => 'approved',
        ]);
        return Order::create([
            'buyer_user_id' => $buyerId,
            'buyer_name' => 'X', 'buyer_email' => 'x@x.com', 'buyer_phone' => '1',
            'buyer_address' => 'a', 'buyer_city' => 'Harare',
            'vendor_id' => $vendor->id, 'fulfilment_track' => 'vendor', 'payment_method' => 'prepaid',
            'status' => $status, 'subtotal' => 100, 'delivery_fee' => 0, 'total' => 100,
            'commission_rate_applied' => 10, 'commission_amount' => 10, 'net_to_vendor' => 90,
            'delivered_at' => $status === 'delivered' ? now() : null,
        ]);
    }

    public function test_buyer_can_confirm_receipt_and_settle(): void
    {
        $buyer = $this->buyer();
        $order = $this->vfOrder($buyer->id, 'delivered');

        $this->actingAs($buyer)->post(route('orders.confirm', $order))->assertRedirect();

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame(90.0, (float) app(WalletService::class)->walletFor($order->vendor)->cached_balance);
    }

    public function test_buyer_cannot_confirm_an_undelivered_order(): void
    {
        $buyer = $this->buyer();
        $order = $this->vfOrder($buyer->id, 'processing');

        $this->actingAs($buyer)->post(route('orders.confirm', $order))->assertSessionHasErrors('order');
        $this->assertSame('processing', $order->fresh()->status);
    }

    public function test_auto_complete_command_completes_stale_delivered_orders(): void
    {
        $fresh = $this->vfOrder($this->buyer()->id, 'delivered');
        $stale = $this->vfOrder($this->buyer()->id, 'delivered');
        $stale->forceFill(['delivered_at' => now()->subDays(10)])->save();

        $this->artisan('orders:auto-complete-vf')->assertExitCode(0);

        $this->assertSame('completed', $stale->fresh()->status); // past the 7-day window
        $this->assertSame('delivered', $fresh->fresh()->status); // still within window
    }
}
