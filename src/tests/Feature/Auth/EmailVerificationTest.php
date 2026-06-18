<?php

namespace Tests\Feature\Auth;

use App\Jobs\Mail\SendVerifyEmailJob;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_prompt_is_shown_to_unverified_user(): void
    {
        /** @var User $user */
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/verify-email')->assertStatus(200);
    }

    public function test_already_verified_user_is_redirected_to_home(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get('/verify-email')->assertRedirect('/');
    }

    public function test_email_can_be_verified_with_valid_otp(): void
    {
        Event::fake();

        /** @var User $user */
        $user = User::factory()->unverified()->create();
        $otp  = $user->generateEmailOtp();

        $this->actingAs($user)
            ->post('/verify-email', ['otp' => $otp])
            ->assertRedirect('/');

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertNull($user->fresh()->email_otp);
    }

    public function test_wrong_otp_is_rejected(): void
    {
        /** @var User $user */
        $user = User::factory()->unverified()->create();
        $user->generateEmailOtp();

        $this->actingAs($user)
            ->post('/verify-email', ['otp' => '000000'])
            ->assertSessionHasErrors('otp');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_expired_otp_is_rejected(): void
    {
        /** @var User $user */
        $user = User::factory()->unverified()->create();
        $otp  = $user->generateEmailOtp();

        $user->updateQuietly(['email_otp_expires_at' => now()->subMinute()]);

        $this->actingAs($user)
            ->post('/verify-email', ['otp' => $otp])
            ->assertSessionHasErrors('otp');
    }

    public function test_non_digits_are_rejected(): void
    {
        /** @var User $user */
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post('/verify-email', ['otp' => 'abcdef'])
            ->assertSessionHasErrors('otp');
    }

    public function test_generate_otp_stores_value_and_expiry(): void
    {
        /** @var User $user */
        $user = User::factory()->unverified()->create();
        $otp  = $user->generateEmailOtp();

        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp);

        $fresh = $user->fresh();
        $this->assertNotNull($fresh->email_otp);
        $this->assertTrue($fresh->email_otp_expires_at->isFuture());
        $this->assertTrue($fresh->isOtpValid($otp));
    }

    public function test_resend_dispatches_new_otp(): void
    {
        Queue::fake();

        /** @var User $user */
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post('/email/verification-notification')
            ->assertRedirect();

        Queue::assertPushed(SendVerifyEmailJob::class);
    }
}
