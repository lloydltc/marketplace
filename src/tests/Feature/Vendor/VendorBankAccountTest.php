<?php

namespace Tests\Feature\Vendor;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorBankAccount;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VendorBankAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function makeVendorWithAdmin(): array
    {
        $vendor = Vendor::create([
            'name'          => 'Bank Test Vendor',
            'slug'          => 'bank-test-vendor',
            'contact_email' => 'bank@test.com',
            'status'        => 'approved',
        ]);
        $user = User::factory()->create(['role' => 'vendor_admin', 'email_verified_at' => now()]);
        $user->assignRole('vendor_admin');
        $vendor->users()->attach($user->id, ['vendor_role' => 'admin', 'joined_at' => now()]);
        return [$vendor, $user];
    }

    public function test_vendor_admin_can_add_bank_account(): void
    {
        [$vendor, $user] = $this->makeVendorWithAdmin();

        $this->actingAs($user)
            ->post(route('vendor.bank-accounts.store'), [
                'bank_name'      => 'FBC Bank',
                'account_number' => '1234567890',
                'account_holder' => 'Test Business Ltd',
            ])
            ->assertRedirect(route('vendor.bank-accounts.index'));

        $this->assertDatabaseHas('vendor_bank_accounts', [
            'vendor_id'  => $vendor->id,
            'bank_name'  => 'FBC Bank',
            'verified_at' => null,
        ]);
    }

    public function test_admin_can_verify_bank_account(): void
    {
        [$vendor, ] = $this->makeVendorWithAdmin();

        $account = VendorBankAccount::create([
            'vendor_id'      => $vendor->id,
            'bank_name'      => 'CBZ Bank',
            'account_number' => '9876543210',
            'account_holder' => 'Test Business Ltd',
        ]);

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.vendors.bank.verify', [$vendor, $account]))
            ->assertRedirect();

        $this->assertNotNull($account->fresh()->verified_at);
    }

    public function test_vendor_cannot_verify_own_bank_account(): void
    {
        [$vendor, $user] = $this->makeVendorWithAdmin();

        $account = VendorBankAccount::create([
            'vendor_id'      => $vendor->id,
            'bank_name'      => 'CBZ Bank',
            'account_number' => '1111111111',
            'account_holder' => 'Test Business Ltd',
        ]);

        $this->actingAs($user)
            ->post(route('admin.vendors.bank.verify', [$vendor, $account]))
            ->assertStatus(403);
    }

    public function test_bank_account_requires_mandatory_fields(): void
    {
        [$vendor, $user] = $this->makeVendorWithAdmin();

        $this->actingAs($user)
            ->post(route('vendor.bank-accounts.store'), [])
            ->assertSessionHasErrors(['bank_name', 'account_number', 'account_holder']);
    }
}
