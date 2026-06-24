<?php

namespace App\Support;

use Illuminate\Contracts\Session\Session;

/**
 * H7: a session-backed set of vehicle ids the buyer wants to compare side-by-side.
 * Works for guests and signed-in users alike (no DB needed). Capped by config so a
 * comparison stays readable and the show query stays bounded.
 */
class CompareList
{
    private const KEY = 'compare.vehicles';

    public function __construct(private readonly Session $session) {}

    private function max(): int
    {
        return max(1, (int) config('engagement.compare.max_items', 4));
    }

    /** @return array<int, string> */
    public function ids(): array
    {
        return array_values(array_filter((array) $this->session->get(self::KEY, [])));
    }

    public function count(): int
    {
        return count($this->ids());
    }

    public function has(string $id): bool
    {
        return in_array($id, $this->ids(), true);
    }

    public function isFull(): bool
    {
        return $this->count() >= $this->max();
    }

    /**
     * Add an id. Returns false (and leaves the set untouched) when the cap is hit
     * and the id isn't already present.
     */
    public function add(string $id): bool
    {
        $ids = $this->ids();

        if (in_array($id, $ids, true)) {
            return true;
        }

        if (count($ids) >= $this->max()) {
            return false;
        }

        $ids[] = $id;
        $this->session->put(self::KEY, $ids);

        return true;
    }

    public function remove(string $id): void
    {
        $this->session->put(self::KEY, array_values(array_diff($this->ids(), [$id])));
    }

    public function clear(): void
    {
        $this->session->forget(self::KEY);
    }
}
