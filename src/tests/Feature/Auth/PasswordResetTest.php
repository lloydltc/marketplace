<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_renders(): void
    {
        $this->get('/forgot-password')->assertStatus(200);
    }

    public function test_reset_link_can_be_requested(): void
    {
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasNoErrors();
    }

    public function test_reset_password_screen_renders_with_valid_token(): void
    {
        $user  = User::factory()->create();
        $token = Password::createToken($user);

        $this->get("/reset-password/{$token}")->assertStatus(200);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user  = User::factory()->create();
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'NewPassword@1234',
            'password_confirmation' => 'NewPassword@1234',
        ])->assertRedirect('/login');

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_expired_token_fails(): void
    {
        $user = User::factory()->create();

        $this->post('/reset-password', [
            'token'                 => 'invalid-token',
            'email'                 => $user->email,
            'password'              => 'NewPassword@1234',
            'password_confirmation' => 'NewPassword@1234',
        ])->assertSessionHasErrors('email');
    }
}
