<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_registration_screen_renders(): void
    {
        $this->get('/register')->assertStatus(200);
    }

    public function test_new_users_can_register_with_valid_data(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'John Doe',
            'email'                 => 'john@example.com',
            'password'              => 'Password@1234',
            'password_confirmation' => 'Password@1234',
        ]);

        $response->assertRedirect('/verify-email');
        $this->assertDatabaseHas('users', ['email' => 'john@example.com', 'role' => 'customer']);
    }

    public function test_registration_fails_with_invalid_email(): void
    {
        $this->post('/register', [
            'name'                  => 'John',
            'email'                 => 'not-an-email',
            'password'              => 'Password@1234',
            'password_confirmation' => 'Password@1234',
        ])->assertSessionHasErrors('email');
    }

    public function test_registration_fails_with_weak_password(): void
    {
        $this->post('/register', [
            'name'                  => 'John',
            'email'                 => 'john@example.com',
            'password'              => 'weak',
            'password_confirmation' => 'weak',
        ])->assertSessionHasErrors('password');
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'john@example.com']);

        $this->post('/register', [
            'name'                  => 'John',
            'email'                 => 'john@example.com',
            'password'              => 'Password@1234',
            'password_confirmation' => 'Password@1234',
        ])->assertSessionHasErrors('email');
    }
}
