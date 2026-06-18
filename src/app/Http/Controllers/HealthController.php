<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * P6: deep health check for uptime monitoring. Beyond the framework's `/up`
 * (which only confirms the app boots), this probes the dependencies a request
 * actually needs — database, cache, storage — and returns 503 if any are down so
 * a load balancer / monitor can react.
 */
class HealthController extends Controller
{
    public function show(): JsonResponse
    {
        $checks = [
            'database' => $this->check(fn () => DB::connection()->getPdo() !== null),
            'cache'    => $this->check(function () {
                $key = 'health:' . Str::random(8);
                Cache::put($key, '1', 5);
                $ok = Cache::get($key) === '1';
                Cache::forget($key);

                return $ok;
            }),
            'storage'  => $this->check(function () {
                // A reachable disk answers exists() without throwing; an
                // unreachable one (e.g. S3 outage) throws → caught as "down".
                Storage::disk(config('filesystems.default'))->exists('.health-probe');

                return true;
            }),
        ];

        $healthy = ! in_array('down', $checks, true);

        return response()->json([
            'status'    => $healthy ? 'ok' : 'degraded',
            'checks'    => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }

    private function check(callable $probe): string
    {
        try {
            return $probe() ? 'ok' : 'down';
        } catch (\Throwable) {
            return 'down';
        }
    }
}
