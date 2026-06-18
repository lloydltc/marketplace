<?php

namespace App\Modules\Settings\Services;

use App\Modules\Settings\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Cached accessor for platform_settings. This is the ONLY supported way to read
 * fee/threshold/limit configuration — every money computation across the app
 * reads from here so values can be tuned without a deploy (BUSINESS_MODEL.md §11).
 *
 * The full key→row map is cached under a single key and invalidated on write.
 */
class SettingsService
{
    private const CACHE_KEY = 'platform_settings.map';

    /**
     * @return array<string, array{value: ?string, type: string}>
     */
    private function map(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return PlatformSetting::query()
                ->get(['key', 'value', 'type'])
                ->keyBy('key')
                ->map(fn (PlatformSetting $s) => ['value' => $s->value, 'type' => $s->type])
                ->all();
        });
    }

    /**
     * Get a setting, cast to its declared type. Returns $default when missing.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $row = $this->map()[$key] ?? null;

        if ($row === null || $row['value'] === null) {
            return $default;
        }

        return $this->cast($row['value'], $row['type']);
    }

    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    public function getDecimal(string $key, float $default = 0.0): float
    {
        return (float) $this->get($key, $default);
    }

    public function getBool(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default);
    }

    public function getString(string $key, string $default = ''): string
    {
        return (string) $this->get($key, $default);
    }

    /**
     * Create or update a setting and invalidate the cache.
     */
    public function set(string $key, mixed $value, ?string $updatedBy = null): PlatformSetting
    {
        $setting = PlatformSetting::firstOrNew(['key' => $key]);

        $setting->value      = $this->serialize($value, $setting->type ?: 'string');
        $setting->updated_by = $updatedBy;
        $setting->save();

        $this->flush();

        return $setting;
    }

    /**
     * Bulk update from an admin form: [key => raw value].
     * Only touches keys that already exist (settings are seeded, not user-created).
     *
     * @param array<string, mixed> $values
     */
    public function updateMany(array $values, ?string $updatedBy = null): void
    {
        $existing = PlatformSetting::query()->get()->keyBy('key');

        foreach ($values as $key => $raw) {
            $setting = $existing->get($key);
            if ($setting === null) {
                continue;
            }

            $setting->value      = $this->serialize($raw, $setting->type);
            $setting->updated_by = $updatedBy;
            $setting->save();
        }

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function cast(string $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'decimal' => (float) $value,
            'boolean' => in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true),
            'json'    => json_decode($value, true),
            default   => $value,
        };
    }

    private function serialize(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => $this->truthy($value) ? '1' : '0',
            'json'    => is_string($value) ? $value : json_encode($value),
            default   => (string) $value,
        };
    }

    private function truthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}
