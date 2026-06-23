<?php

namespace App\Console\Commands;

use App\Modules\Vehicles\Models\Vehicle;
use App\Notifications\VehicleListingNotification;
use Illuminate\Console\Command;

/**
 * D5: warn sellers before their vehicle listing lapses. Runs daily and notifies
 * for listings expiring in exactly 7 or 1 days (so each seller gets at most two
 * reminders, not a daily nag).
 */
class SendVehicleExpiryReminders extends Command
{
    protected $signature = 'vehicles:expiry-reminders';

    protected $description = 'Email sellers whose vehicle listings are about to expire';

    /** Days-before-expiry on which to remind. */
    private const WINDOWS = [7, 1];

    public function handle(): int
    {
        $sent = 0;

        foreach (self::WINDOWS as $days) {
            $from = now()->addDays($days)->startOfDay();
            $to   = now()->addDays($days)->endOfDay();

            Vehicle::query()->expiringBetween($from, $to)->chunkById(100, function ($vehicles) use (&$sent) {
                foreach ($vehicles as $vehicle) {
                    $vehicle->ownerUser()?->notify(new VehicleListingNotification($vehicle, 'expiring'));
                    $sent++;
                }
            });
        }

        $this->info("Sent {$sent} expiry reminder(s).");

        return self::SUCCESS;
    }
}
