<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R9: the vendor application is now a multi-step wizard (UI_STANDARDS.md). The
 * server flow is unchanged — every field still posts at once — so this verifies
 * the page renders as a wizard and a full submission still creates a pending
 * vendor profile the applicant can see immediately (F12).
 */
class VendorApplicationWizardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_form_renders_as_a_wizard(): void
    {
        $this->get(route('apply.vendor'))
            ->assertOk()
            ->assertSee('Step', false)
            ->assertSee('of 2', false)
            ->assertSee('Continue'); // step-1 advance button
    }

    public function test_full_submission_creates_pending_vendor_and_logs_in(): void
    {
        $response = $this->post(route('apply.vendor.store'), [
            'name'                  => 'Jane Vendor',
            'email'                 => 'jane@vendor.test',
            'password'              => 'Sup3rSecret!9',
            'password_confirmation' => 'Sup3rSecret!9',
            'business_name'         => 'Jane Auto Spares',
            'phone'                 => '+263771234567',
            'address'               => '12 Samora Machel Ave, Harare',
        ]);

        $response->assertRedirect(route('verification.notice'));

        $this->assertDatabaseHas('users', [
            'email'  => 'jane@vendor.test',
            'role'   => 'vendor_admin',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('vendors', [
            'name'   => 'Jane Auto Spares',
            'status' => 'pending',
        ]);

        $user = User::where('email', 'jane@vendor.test')->first();
        $this->assertAuthenticatedAs($user);
        // F12: profile exists immediately in pending status.
        $this->assertNotNull($user->vendor);
        $this->assertTrue($user->vendor->isPending());
    }

    public function test_validation_errors_return_to_form(): void
    {
        $this->from(route('apply.vendor'))
            ->post(route('apply.vendor.store'), ['name' => ''])
            ->assertRedirect(route('apply.vendor'))
            ->assertSessionHasErrors(['name', 'email', 'password', 'business_name']);
    }
}
