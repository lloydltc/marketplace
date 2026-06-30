<?php

namespace App\Http\Controllers;

use App\Modules\Notifications\Services\NotificationChannelResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AC1: in-app notification inbox + per-type channel preferences. Available to every
 * authenticated role (not just buyers).
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function read(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        // Deep-link to the notification's target when present.
        $url = $notification->data['url'] ?? null;

        return $url ? redirect()->away($url) : back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'All notifications marked as read.');
    }

    public function preferences(Request $request): View
    {
        return view('notifications.preferences', [
            'types'    => config('notifications.types', []),
            'channels' => array_filter(config('notifications.channels', []), fn ($c) => $c['enabled']),
            'current'  => $request->user()->notificationPreferences()->get()->groupBy('type'),
        ]);
    }

    public function updatePreferences(Request $request, NotificationChannelResolver $resolver): RedirectResponse
    {
        $user = $request->user();
        $selected = (array) $request->input('prefs', []); // [type][channel] = "1"

        foreach (config('notifications.types', []) as $type => $def) {
            foreach (array_keys(array_filter(config('notifications.channels', []), fn ($c) => $c['enabled'])) as $channel) {
                $resolver->setPreference($user, $type, $channel, isset($selected[$type][$channel]));
            }
        }

        return back()->with('status', 'Notification preferences saved.');
    }
}
