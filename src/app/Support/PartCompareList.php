<?php

namespace App\Support;

use Illuminate\Contracts\Session\Session;

/**
 * PM8: session-backed set of canonical part ids to compare side-by-side. Mirrors
 * the H7 vehicle CompareList. Capped by config; works for guests + signed-in.
 */
class PartCompareList
{
    private const KEY = 'compare.parts';

    public function __construct(private readonly Session $session) {}

    private function max(): int
    {
        return max(1, (int) config('parts.compare_max', 4));
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
