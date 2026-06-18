<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Orders\Models\Order;
use App\Modules\Products\Models\Product;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * R7: dashboard KPIs are query-backed (no hardcoded "—"), and the vendor figures
 * are scoped to the acting vendor only.
 */
class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function staff(string $role): User
    {
        $u = User::factory()->create([
            'role' => $role, 'status' => 'active',
            'email_verified_at' => now(), 'force_password_change' => false,
        ]);
        $u->assignRole($role);

        return $u;
    }

    private function vendor(string $status = 'approved'): Vendor
    {
        return Vendor::create([
            'name' => 'V ' . Str::random(4),
            'slug' => 'v-' . Str::random(6),
            'contact_email' => Str::random(5) . '@x.com',
            'status' => $status,
        ]);
    }

    private function category(): Category
    {
        return Category::create(['name' => 'Parts', 'slug' => 'p-' . Str::random(6), 'sort_order' => 0]);
    }

    private function product(Vendor $v, Category $c, string $status): Product
    {
        return Product::create([
            'vendor_id' => $v->id, 'category_id' => $c->id,
            'title' => 'Item ' . Str::random(4), 'description' => 'x',
            'price_zwl' => 100, 'quantity' => 5, 'status' => $status,
        ]);
    }

    public function test_admin_dashboard_shows_real_counts(): void
    {
        $admin = $this->staff('super_admin');

        $approved = $this->vendor('approved');
        $this->vendor('pending'); // not counted in active_vendors
        $cat = $this->category();
        $this->product($approved, $cat, 'active');
        $this->product($approved, $cat, 'active');
        $this->product($approved, $cat, 'pending'); // counts toward pending_approvals

        // A pending private-seller application.
        $applicant = User::factory()->create(['role' => 'private_seller', 'status' => 'pending']);
        $applicant->assignRole('private_seller');

        $stats = $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->viewData('stats');

        $this->assertSame(User::count(), $stats['total_users']);
        $this->assertSame(1, $stats['active_vendors']);
        $this->assertSame(2, $stats['listings']);
        // 1 pending product + 1 pending application
        $this->assertSame(2, $stats['pending_approvals']);
    }

    public function test_vendor_dashboard_stats_are_scoped_to_own_vendor(): void
    {
        $vendorA = $this->vendor();
        $admin = $this->staff('vendor_admin');
        $vendorA->users()->attach($admin->id, ['vendor_role' => 'admin', 'joined_at' => now()]);

        $vendorB = $this->vendor();
        $cat = $this->category();

        // Vendor A: 2 active listings + 1 pending (not counted)
        $this->product($vendorA, $cat, 'active');
        $this->product($vendorA, $cat, 'active');
        $this->product($vendorA, $cat, 'pending');

        // Vendor B has listings too — must NOT leak into A's stats.
        $this->product($vendorB, $cat, 'active');

        // A paid order awaiting fulfilment for vendor A.
        Order::create([
            'order_number' => 'ORD-' . Str::random(6),
            'buyer_user_id' => $admin->id,
            'buyer_name' => 'Buyer', 'buyer_email' => 'b@x.com',
            'buyer_phone' => '0700000000', 'buyer_address' => 'Addr', 'buyer_city' => 'Harare',
            'vendor_id' => $vendorA->id,
            'fulfilment_track' => 'vendor',
            'payment_method' => 'prepaid',
            'status' => 'paid',
            'subtotal' => 100, 'total' => 100, 'currency' => 'ZWL',
        ]);

        $stats = $this->actingAs($admin)
            ->get(route('vendor.dashboard'))
            ->assertOk()
            ->viewData('stats');

        $this->assertSame(2, $stats['active_listings']);
        $this->assertSame(1, $stats['pending_orders']);
        $this->assertSame(1, $stats['team_members']);
    }
}
