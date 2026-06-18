<?php

namespace Tests\Feature\Security;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorBankAccount;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * P3: security hardening — response headers, encryption at rest for bank details,
 * and audit logging of privileged money actions.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
    }

    // ─── Security headers ─────────────────────────────────────────────────────────

    public function test_security_headers_present_on_web_response(): void
    {
        $res = $this->get(route('home'))->assertOk();

        $res->assertHeader('X-Content-Type-Options', 'nosniff');
        $res->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $res->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertNotNull($res->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("default-src 'self'", $res->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("object-src 'none'", $res->headers->get('Content-Security-Policy'));
    }

    // ─── Bank details encrypted at rest ─────────────────────────────────────────────

    private function vendor(): Vendor
    {
        return Vendor::create([
            'name' => 'V ' . Str::random(4), 'slug' => 'v-' . Str::random(6),
            'contact_email' => 'v@x.com', 'status' => 'approved',
        ]);
    }

    public function test_bank_account_number_is_encrypted_at_rest(): void
    {
        $vendor = $this->vendor();
        $account = VendorBankAccount::create([
            'vendor_id' => $vendor->id,
            'bank_name' => 'FBC Bank',
            'account_number' => '1234567890',
            'account_holder' => 'Test Ltd',
        ]);

        // Raw DB value must NOT be the plaintext.
        $raw = DB::table('vendor_bank_accounts')->where('id', $account->id)->value('account_number');
        $this->assertNotSame('1234567890', $raw);
        $this->assertNotEmpty($raw);

        // The model transparently decrypts it.
        $this->assertSame('1234567890', $account->fresh()->account_number);
        $this->assertSame('******7890', $account->maskedAccountNumber());

        // A deterministic hash is stored for uniqueness.
        $hash = DB::table('vendor_bank_accounts')->where('id', $account->id)->value('account_number_hash');
        $this->assertNotEmpty($hash);
        $this->assertSame(64, strlen($hash));
    }

    public function test_duplicate_account_number_per_vendor_is_rejected(): void
    {
        $vendor = $this->vendor();
        VendorBankAccount::create([
            'vendor_id' => $vendor->id, 'bank_name' => 'FBC',
            'account_number' => '5555555555', 'account_holder' => 'A',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        VendorBankAccount::create([
            'vendor_id' => $vendor->id, 'bank_name' => 'FBC',
            'account_number' => '5555555555', 'account_holder' => 'B',
        ]);
    }

    // ─── Privileged action audit logging ────────────────────────────────────────────

    public function test_manual_wallet_adjustment_is_audit_logged(): void
    {
        $vendor = $this->vendor();
        $admin = User::factory()->create([
            'role' => 'super_admin', 'status' => 'active',
            'email_verified_at' => now(), 'force_password_change' => false,
        ]);
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->post(route('admin.vendors.wallet.adjust', $vendor), [
                'amount' => 25.00, 'direction' => 'credit', 'reason' => 'Goodwill credit',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id'    => $admin->id,
            'action'      => 'wallet.manual_adjustment',
            'target_id'   => $vendor->id,
        ]);
    }
}
