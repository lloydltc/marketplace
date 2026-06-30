<?php

namespace App\Modules\Notifications\Services;

use App\Models\User;
use App\Modules\Notifications\Models\NotificationPreference;

/**
 * AC1: resolves which channels a notification type should deliver on for a user —
 * config default per type, overridden by the user's saved preferences, then
 * intersected with the globally-enabled channels. Maps logical channels
 * (in_app/email/push) to Laravel channel names.
 */
class NotificationChannelResolver
{
    private const MAP = ['in_app' => 'database', 'email' => 'mail', 'push' => 'push'];

    /**
     * Logical channels enabled for this user + type.
     *
     * @return list<string>
     */
    public function channels(User $user, string $type): array
    {
        // Note: type keys contain dots (e.g. "alert.new_match"), so index the
        // types array literally rather than via config() dot-access.
        $types = (array) config('notifications.types', []);
        $default = (array) ($types[$type]['default'] ?? ['in_app']);
        $overrides = NotificationPreference::where('user_id', $user->id)
            ->where('type', $type)->pluck('enabled', 'channel');

        $enabled = [];
        foreach (array_keys(config('notifications.channels', [])) as $channel) {
            if (! config("notifications.channels.{$channel}.enabled")) {
                continue; // globally off (e.g. push without infra)
            }
            // User override wins; otherwise the type's config default.
            $on = $overrides->has($channel) ? (bool) $overrides[$channel] : in_array($channel, $default, true);
            if ($on) {
                $enabled[] = $channel;
            }
        }

        return $enabled;
    }

    /**
     * Laravel channel names (database/mail/...) for a user + type.
     *
     * @return list<string>
     */
    public function laravelChannels(User $user, string $type): array
    {
        return array_values(array_filter(array_map(
            fn ($c) => self::MAP[$c] ?? null,
            $this->channels($user, $type),
        )));
    }

    public function setPreference(User $user, string $type, string $channel, bool $enabled): void
    {
        NotificationPreference::updateOrCreate(
            ['user_id' => $user->id, 'type' => $type, 'channel' => $channel],
            ['enabled' => $enabled],
        );
    }
}
