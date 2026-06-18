<?php

namespace App\Modules\Vehicles\Listeners;

use App\Mail\VehicleApprovedMailable;
use App\Modules\Vehicles\Events\VehicleApprovedEvent;
use Illuminate\Support\Facades\Mail;

class SendVehicleApprovedNotification
{
    public function handle(VehicleApprovedEvent $event): void
    {
        $vehicle = $event->vehicle;

        if ($vehicle->isListedByVendor()) {
            $vehicle->load('vendor.admins');
            foreach ($vehicle->vendor->admins as $admin) {
                Mail::to($admin->email)->queue(new VehicleApprovedMailable($vehicle));
            }
        } else {
            $vehicle->load('seller');
            if ($vehicle->seller) {
                Mail::to($vehicle->seller->email)->queue(new VehicleApprovedMailable($vehicle));
            }
        }
    }
}
