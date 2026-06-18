<?php

namespace App\Modules\Vendors\Listeners;

use App\Modules\Vendors\Events\VendorRejectedEvent;
use App\Modules\Vendors\Jobs\Mail\SendVendorRejectedJob;

class SendVendorRejectedNotification
{
    public function handle(VendorRejectedEvent $event): void
    {
        dispatch(new SendVendorRejectedJob($event->vendor, $event->reason));
    }
}
