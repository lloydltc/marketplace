<?php

namespace App\Console\Commands;

use App\Modules\Analytics\Services\AnalyticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * H5: roll yesterday's (and today's, for freshness) raw listing events into daily
 * stats, then prune raw events past the retention window. Idempotent.
 */
class AggregateListingAnalytics extends Command
{
    protected $signature = 'analytics:aggregate {--retain-days=7 : Days of raw events to keep}';

    protected $description = 'Aggregate raw listing events into daily stats and prune old raw rows';

    public function handle(AnalyticsService $analytics): int
    {
        // Aggregate today and yesterday (covers the day boundary).
        $today     = $analytics->aggregateDay(Carbon::today());
        $yesterday = $analytics->aggregateDay(Carbon::yesterday());

        $pruned = $analytics->pruneRawEventsBefore(Carbon::today()->subDays((int) $this->option('retain-days')));

        $this->info("Aggregated {$today} (today) + {$yesterday} (yesterday) stat rows; pruned {$pruned} old raw events.");

        return self::SUCCESS;
    }
}
