<?php

namespace App\Support;

use Illuminate\Support\Facades\Cookie;
use Illuminate\Http\Request;

/**
 * H7: a per-browser list of recently-viewed vehicle ids, kept in a long-lived
 * cookie (no auth required, no PII). Newest first, capped by config.
 */
class RecentlyViewed
{
    private const COOKIE = 'recently_viewed_vehicles';

    public function __construct(private readonly Request $request) {}

    private function max(): int
    {
        return max(1, (int) config('engagement.recently_viewed.max', 10));
    }

    /** @return array<int, string> */
    public function ids(): array
    {
        $raw = (string) $this->request->cookie(self::COOKIE, '');

        return array_values(array_filter(explode(',', $raw)));
    }

    /**
     * Record a view: move/insert the id at the front, de-duplicate, cap, and queue
     * the refreshed cookie onto the outgoing response.
     */
    public function record(string $id): void
    {
        $ids = array_values(array_diff($this->ids(), [$id]));
        array_unshift($ids, $id);
        $ids = array_slice($ids, 0, $this->max());

        Cookie::queue(
            self::COOKIE,
            implode(',', $ids),
            60 * 24 * max(1, (int) config('engagement.recently_viewed.cookie_days', 30))
        );
    }
}
