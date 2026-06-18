<?php

namespace App\Modules\Vehicles\Listeners;

use App\Mail\VehicleRejectedMailable;
use App\Modules\Vehicles\Events\VehicleRejectedEvent;
use Illuminate\Support\Facades\Mail;

class SendVehicleRejectedNotification
{
    public function handle(VehicleRejectedEvent $event): void
    {
        $vehicle = $event->vehicle;

        if ($vehicle->isListedByVendor()) {
            $vehicle->load('vendor.admins');
            foreach ($vehicle->vendor->admins as $admin) {
                Mail::to($admin->email)->queue(new VehicleRejectedMailable($vehicle, $event->reason));
            }
        } else {
            $vehicle->load('seller');
            if ($vehicle->seller) {
                Mail::to($vehicle->seller->email)->queue(new VehicleRejectedMailable($vehicle, $event->reason));
            }
        }
    }
}
