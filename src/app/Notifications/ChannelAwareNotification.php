<?php

namespace App\Notifications;

use App\Modules\Notifications\Services\NotificationChannelResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * AC1: base for engagement notifications. Subclasses declare their notification
 * `type()` (matching config/notifications.php) and an in-app payload; channels
 * are resolved per-user from preferences + config — so one notification fans out
 * to in-app / email / push exactly as the user wants. No per-class channel lists.
 */
abstract class ChannelAwareNotification extends Notification
{
    use Queueable;

    /** Config notification type key, e.g. "alert.price_drop". */
    abstract public function type(): string;

    /**
     * In-app payload (also the database channel record).
     *
     * @return array<string, mixed>
     */
    abstract public function payload(object $notifiable): array;

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return app(NotificationChannelResolver::class)->laravelChannels($notifiable, $this->type());
    }

    /** Database (in-app) channel record. */
    public function toArray(object $notifiable): array
    {
        return $this->payload($notifiable) + ['type' => $this->type()];
    }
}
