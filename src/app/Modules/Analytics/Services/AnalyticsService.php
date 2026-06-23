<?php

namespace App\Modules\Analytics\Services;

use App\Modules\Analytics\Models\ListingDailyStat;
use App\Modules\Analytics\Models\ListingEvent;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * H5: ingest + aggregate per-listing analytics. Integrity is the point — events
 * are bot-filtered and deduped (one per visitor/type/listing/day) so refreshes
 * and crawlers can't inflate counts.
 */
class AnalyticsService
{
    public const TYPES = ['detail_view', 'phone_reveal', 'call_click', 'whatsapp_click', 'enquiry'];

    /** Conservative bot/user-agent filter. */
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|bing|google|yandex|baidu|duckduck|facebookexternalhit|whatsapp|telegram|headless|preview|monitor|curl|wget|python-requests|httpclient|scrapy/i';

    /**
     * Record an event for a listing. No-op for bots; deduped per visitor/day.
     * Returns true if a new event row was created.
     */
    public function record(string $type, Vehicle $subject, Request $request): bool
    {
        if (! in_array($type, self::TYPES, true)) {
            return false;
        }

        $ua = (string) $request->userAgent();
        if ($this->isBot($ua)) {
            return false;
        }

        $hash = $this->visitorHash($request, $subject->id, $ua);
        $today = Carbon::today()->toDateString();

        // insertOrIgnore against the dedupe unique index — atomic, no race.
        $created = ListingEvent::query()->insertOrIgnore([[
            'id'             => (string) Str::uuid(),
            'subject_type'   => $subject::class,
            'subject_id'     => $subject->id,
            'seller_user_id' => $subject->user_id,
            'vendor_id'      => $subject->vendor_id,
            'type'           => $type,
            'visitor_hash'   => $hash,
            'occurred_on'    => $today,
            'created_at'     => now(),
        ]]);

        return $created > 0;
    }

    public function isBot(string $userAgent): bool
    {
        return $userAgent === '' || (bool) preg_match(self::BOT_PATTERN, $userAgent);
    }

    private function visitorHash(Request $request, string $subjectId, string $ua): string
    {
        // Per-visitor identity that doesn't store raw PII: ip + UA + listing,
        // salted with the app key. (Same person, same listing → same hash.)
        return hash_hmac('sha256', $request->ip() . '|' . $ua . '|' . $subjectId, (string) config('app.key'));
    }

    /**
     * Roll raw events for a given day into daily stats (idempotent), then return
     * the number of stat rows written. Called by the daily aggregation command.
     */
    public function aggregateDay(Carbon $day): int
    {
        $date = $day->toDateString();

        $rows = ListingEvent::query()
            ->selectRaw('subject_type, subject_id, seller_user_id, vendor_id, type, count(*) as c')
            ->where('occurred_on', $date)
            ->groupBy('subject_type', 'subject_id', 'seller_user_id', 'vendor_id', 'type')
            ->get();

        foreach ($rows as $r) {
            ListingDailyStat::updateOrCreate(
                ['subject_id' => $r->subject_id, 'type' => $r->type, 'stat_date' => $date],
                [
                    'subject_type'   => $r->subject_type,
                    'seller_user_id' => $r->seller_user_id,
                    'vendor_id'      => $r->vendor_id,
                    'count'          => $r->c,
                ],
            );
        }

        return $rows->count();
    }

    /** Prune raw events older than the retention window (kept only until aggregated). */
    public function pruneRawEventsBefore(Carbon $cutoff): int
    {
        return ListingEvent::where('occurred_on', '<', $cutoff->toDateString())->delete();
    }

    /**
     * Per-type totals for a listing over a date range, combining aggregated daily
     * stats with today's not-yet-aggregated raw events.
     *
     * @return array<string, int>
     */
    public function totalsForListing(Vehicle $vehicle, Carbon $from, Carbon $to): array
    {
        $agg = ListingDailyStat::where('subject_id', $vehicle->id)
            ->whereBetween('stat_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('type')->selectRaw('type, sum(count) as c')->pluck('c', 'type')->all();

        // Today's raw events (not yet aggregated) when the range includes today.
        if ($to->isToday()) {
            $todayRaw = ListingEvent::where('subject_id', $vehicle->id)
                ->where('occurred_on', Carbon::today()->toDateString())
                ->groupBy('type')->selectRaw('type, count(*) as c')->pluck('c', 'type')->all();
            foreach ($todayRaw as $type => $c) {
                // Avoid double counting if today was already aggregated.
                if (! ListingDailyStat::where('subject_id', $vehicle->id)->where('type', $type)->whereDate('stat_date', Carbon::today())->exists()) {
                    $agg[$type] = ($agg[$type] ?? 0) + $c;
                }
            }
        }

        $out = [];
        foreach (self::TYPES as $t) {
            $out[$t] = (int) ($agg[$t] ?? 0);
        }

        return $out;
    }
}
