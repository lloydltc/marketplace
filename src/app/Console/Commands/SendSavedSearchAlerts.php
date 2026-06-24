<?php

namespace App\Console\Commands;

use App\Models\SavedSearch;
use App\Modules\Vehicles\Repositories\VehicleRepositoryInterface;
use App\Notifications\SavedSearchAlertNotification;
use Illuminate\Console\Command;

/**
 * H7: email buyers when new listings match a saved search they've opted into
 * alerts for. "New" means published after the previous digest (last_notified_at),
 * so a buyer is never told twice about the same listing. First run for a search
 * uses a 24h look-back so we don't dump the entire back-catalogue on them.
 *
 * Vehicle saved searches only for now (the dominant case); product alerts can
 * follow the same shape.
 */
class SendSavedSearchAlerts extends Command
{
    protected $signature = 'alerts:saved-searches';

    protected $description = 'Email buyers about new listings matching their saved searches';

    public function handle(VehicleRepositoryInterface $vehicles): int
    {
        $perEmail = max(1, (int) config('engagement.alerts.max_per_email', 12));
        $sent = 0;

        SavedSearch::query()
            ->alerting()
            ->where('type', 'vehicles')
            ->with('user')
            ->chunkById(100, function ($searches) use ($vehicles, $perEmail, &$sent) {
                foreach ($searches as $search) {
                    if ($search->user === null) {
                        continue;
                    }

                    $since = $search->last_notified_at ?? now()->subDay();

                    $filters = array_merge(
                        (array) ($search->query_params ?? []),
                        ['created_after' => $since]
                    );

                    $matches = $vehicles->paginatePublic($filters, $perEmail);

                    // Always advance the high-water mark so the next run's window
                    // starts here — even when there were no matches this time.
                    $search->forceFill(['last_notified_at' => now()])->save();

                    if ($matches->total() === 0) {
                        continue;
                    }

                    $search->user->notify(new SavedSearchAlertNotification(
                        $search,
                        $matches->getCollection(),
                        $matches->total(),
                    ));
                    $sent++;
                }
            });

        $this->info("Sent {$sent} saved-search alert(s).");

        return self::SUCCESS;
    }
}
