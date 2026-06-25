<?php

namespace App\Modules\Vehicles\Services;

use App\Modules\Vehicles\Models\VehicleEngine;
use App\Modules\Vehicles\Models\VehicleGeneration;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use App\Modules\Vehicles\Models\VehicleTransmission;
use App\Modules\Vehicles\Models\VehicleVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * PM0: cached read access to the canonical vehicle taxonomy cascade
 * (make → model → generation → variant) plus the engine/transmission lookups.
 * Powers the cascading fitment selector and admin dropdowns.
 *
 * Store-agnostic caching: every key carries a version number; flush() bumps the
 * version so all prior entries are abandoned (works on redis or the array store
 * used in tests — no cache tags required).
 */
class TaxonomyService
{
    private const TTL = 3600; // 1 hour
    private const VERSION_KEY = 'taxonomy:version';

    private function key(string $suffix): string
    {
        $version = Cache::get(self::VERSION_KEY, 1);

        return "taxonomy:v{$version}:{$suffix}";
    }

    /** @return Collection<int, array{id: string, name: string}> */
    public function makes(): Collection
    {
        return Cache::remember($this->key('makes'), self::TTL, fn () => VehicleMake::query()
            ->active()->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'name'])->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])->values());
    }

    /** @return Collection<int, array{id: string, name: string}> */
    public function models(string $makeId): Collection
    {
        return Cache::remember($this->key("models:{$makeId}"), self::TTL, fn () => VehicleModel::query()
            ->where('make_id', $makeId)->active()->orderBy('name')
            ->get(['id', 'name'])->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])->values());
    }

    /** @return Collection<int, array{id: string, name: string, year_start: ?int, year_end: ?int}> */
    public function generations(string $modelId): Collection
    {
        return Cache::remember($this->key("generations:{$modelId}"), self::TTL, fn () => VehicleGeneration::query()
            ->where('model_id', $modelId)->active()->orderByDesc('year_start')
            ->get(['id', 'name', 'year_start', 'year_end'])
            ->map(fn ($g) => ['id' => $g->id, 'name' => $g->name, 'year_start' => $g->year_start, 'year_end' => $g->year_end])->values());
    }

    /** @return Collection<int, array{id: string, name: string}> */
    public function variants(string $modelId, ?string $generationId = null): Collection
    {
        $cacheKey = "variants:{$modelId}:" . ($generationId ?? 'all');

        return Cache::remember($this->key($cacheKey), self::TTL, fn () => VehicleVariant::query()
            ->where('model_id', $modelId)->active()
            ->when($generationId, fn ($q) => $q->where('generation_id', $generationId))
            ->orderBy('name')
            ->get(['id', 'name'])->map(fn ($v) => ['id' => $v->id, 'name' => $v->name])->values());
    }

    /** @return Collection<int, array{id: string, code: string}> */
    public function engines(): Collection
    {
        return Cache::remember($this->key('engines'), self::TTL, fn () => VehicleEngine::query()
            ->active()->orderBy('code')
            ->get(['id', 'code'])->map(fn ($e) => ['id' => $e->id, 'code' => $e->code])->values());
    }

    /** @return Collection<int, array{id: string, type: string}> */
    public function transmissions(): Collection
    {
        return Cache::remember($this->key('transmissions'), self::TTL, fn () => VehicleTransmission::query()
            ->active()->orderBy('type')
            ->get(['id', 'type'])->map(fn ($t) => ['id' => $t->id, 'type' => $t->type])->values());
    }

    /** Invalidate the whole cascade cache (call after any taxonomy write — PM9). */
    public function flush(): void
    {
        Cache::put(self::VERSION_KEY, Cache::get(self::VERSION_KEY, 1) + 1);
    }
}
