<?php

namespace App\Console\Commands;

use App\Models\SavedSearch;
use App\Modules\Notifications\Models\ListingPriceHistory;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Repositories\VehicleRepositoryInterface;
use App\Notifications\PriceDropNotification;
use Illuminate\Console\Command;

/**
 * AC2: notify saved-search owners when a vehicle matching their search drops in
 * price. Sources deterministic price-drop rows; dedupes via alerted_at (across
 * runs) and a per-run guard (one alert per user+vehicle). Vehicles only for now.
 */
class SendPriceDropAlerts extends Command
{
    protected $signature = 'alerts:price-drops';

    protected $description = 'Notify saved-search owners about price drops on matching vehicles';

    public function handle(VehicleRepositoryInterface $vehicles): int
    {
        $alertingSearches = SavedSearch::query()->alerting()->where('type', 'vehicles')->with('user')->get();
        $sent = 0;

        ListingPriceHistory::query()
            ->where('subject_type', Vehicle::class)
            ->where('is_drop', true)
            ->whereNull('alerted_at')
            ->orderBy('created_at')
            ->chunkById(200, function ($drops) use ($vehicles, $alertingSearches, &$sent) {
                foreach ($drops as $drop) {
                    $vehicle = Vehicle::find($drop->subject_id);
                    if ($vehicle && $vehicle->isActive()) {
                        $notifiedUsers = [];
                        foreach ($alertingSearches as $search) {
                            if ($search->user === null || in_array($search->user_id, $notifiedUsers, true)) {
                                continue;
                            }
                            if ($vehicles->matchesPublicly($vehicle->id, (array) ($search->query_params ?? []))) {
                                $search->user->notify(new PriceDropNotification(
                                    $search, $vehicle, (float) $drop->old_price, (float) $drop->new_price, $drop->currency,
                                ));
                                $notifiedUsers[] = $search->user_id;
                                $sent++;
                            }
                        }
                    }

                    $drop->forceFill(['alerted_at' => now()])->save();
                }
            });

        $this->info("Sent {$sent} price-drop alert(s).");

        return self::SUCCESS;
    }
}
