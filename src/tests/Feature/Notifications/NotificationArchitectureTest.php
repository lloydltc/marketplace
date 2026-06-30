<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\ChannelAwareNotification;
use App\Modules\Notifications\Models\NotificationPreference;
use App\Modules\Notifications\Services\NotificationChannelResolver;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** A concrete notification for exercising the shared architecture. */
class TestAlertNotification extends ChannelAwareNotification
{
    public function type(): string
    {
        return 'alert.new_match';
    }

    public function payload(object $notifiable): array
    {
        return ['title' => 'Test alert', 'body' => 'A match was found', 'url' => null];
    }

    public function toMail(object $notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        return (new \Illuminate\Notifications\Messages\MailMessage())->line('Test alert');
    }
}

/**
 * AC1: the shared notification spine — config-driven channel resolution, user
 * preference overrides, in-app inbox, preferences UI.
 */
class NotificationArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
    }

    private function user(): User
    {
        $u = User::factory()->create(['role' => 'customer', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $u->assignRole('customer');

        return $u;
    }

    public function test_resolver_uses_config_defaults(): void
    {
        $resolver = app(NotificationChannelResolver::class);
        $user = $this->user();

        $this->assertSame(['in_app', 'email'], $resolver->channels($user, 'alert.new_match'));
        $this->assertSame(['database', 'mail'], $resolver->laravelChannels($user, 'alert.new_match'));
    }

    public function test_user_override_disables_a_channel(): void
    {
        $resolver = app(NotificationChannelResolver::class);
        $user = $this->user();

        $resolver->setPreference($user, 'alert.new_match', 'email', false);

        $this->assertSame(['in_app'], $resolver->channels($user, 'alert.new_match'));
    }

    public function test_globally_disabled_push_is_never_used(): void
    {
        config(['notifications.channels.push.enabled' => false]);
        $resolver = app(NotificationChannelResolver::class);
        $user = $this->user();

        // Even if the user turns push on, it stays off platform-wide.
        $resolver->setPreference($user, 'alert.new_match', 'push', true);

        $this->assertNotContains('push', $resolver->channels($user, 'alert.new_match'));
    }

    public function test_channel_aware_notification_writes_in_app_record(): void
    {
        $user = $this->user();

        $user->notify(new TestAlertNotification());

        $this->assertSame(1, $user->notifications()->count());
        $this->assertSame('alert.new_match', $user->notifications()->first()->data['type']);
    }

    public function test_inbox_renders_and_marks_read(): void
    {
        $user = $this->user();
        $user->notify(new TestAlertNotification());

        $this->actingAs($user)->get(route('notifications.index'))->assertOk()->assertSee('Test alert');

        $id = $user->notifications()->first()->id;
        $this->actingAs($user)->get(route('notifications.read', $id))->assertRedirect();
        $this->assertNotNull($user->notifications()->first()->read_at);
    }

    public function test_preferences_update_persists(): void
    {
        $user = $this->user();

        $this->actingAs($user)->put(route('notifications.preferences.update'), [
            'prefs' => ['alert.price_drop' => ['in_app' => '1']], // email left off
        ])->assertRedirect();

        $this->assertDatabaseHas('notification_preferences', ['user_id' => $user->id, 'type' => 'alert.price_drop', 'channel' => 'in_app', 'enabled' => true]);
        $this->assertDatabaseHas('notification_preferences', ['user_id' => $user->id, 'type' => 'alert.price_drop', 'channel' => 'email', 'enabled' => false]);
    }
}
