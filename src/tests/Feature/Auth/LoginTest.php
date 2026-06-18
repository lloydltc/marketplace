<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_renders(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_users_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email'             => 'user@example.com',
            'password'          => bcrypt('Password@1234'),
            'email_verified_at' => now(),
            'role'              => 'customer',
        ]);

        $this->post('/login', [
            'email'    => 'user@example.com',
            'password' => 'Password@1234',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email'    => 'user@example.com',
            'password' => bcrypt('Password@1234'),
        ]);

        $this->post('/login', [
            'email'    => 'user@example.com',
            'password' => 'WrongPassword!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_unverified_user_is_redirected_to_verification_notice(): void
    {
        User::factory()->create([
            'email'             => 'unverified@example.com',
            'password'          => bcrypt('Password@1234'),
            'email_verified_at' => null,
            'role'              => 'customer',
        ]);

        $this->post('/login', [
            'email'    => 'unverified@example.com',
            'password' => 'Password@1234',
        ])->assertRedirect();

        // The landing is public, but any verified-gated route bounces an
        // unverified user to the verification notice.
        $this->get(route('saved-searches.index'))->assertRedirect('/verify-email');
    }
}
