<?php

namespace App\Console\Commands;

use App\Modules\Vehicles\Models\Vehicle;
use App\Notifications\VehicleListingNotification;
use Illuminate\Console\Command;

/**
 * D5: sweep active vehicle listings whose expiry has lapsed → 'expired'. Expired
 * listings drop out of the public catalogue/search but remain on the seller
 * dashboard with a Renew action. The owner is notified.
 */
class ExpireVehicles extends Command
{
    protected $signature = 'vehicles:expire';

    protected $description = 'Expire active vehicle listings past their expiry date';

    public function handle(): int
    {
        $count = 0;

        Vehicle::query()
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->chunkById(100, function ($vehicles) use (&$count) {
                foreach ($vehicles as $vehicle) {
                    $vehicle->update(['status' => 'expired']);
                    $vehicle->ownerUser()?->notify(new VehicleListingNotification($vehicle, 'expired'));
                    $count++;
                }
            });

        $this->info("Expired {$count} vehicle listing(s).");

        return self::SUCCESS;
    }
}
