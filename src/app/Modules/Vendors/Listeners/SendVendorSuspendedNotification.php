<?php

namespace App\Modules\Vendors\Listeners;

use App\Jobs\Mail\SendAccountSuspendedJob;
use App\Modules\Vendors\Events\VendorSuspendedEvent;

class SendVendorSuspendedNotification
{
    public function handle(VendorSuspendedEvent $event): void
    {
        // The vendor_admin (first admin) receives the suspension email
        $admin = $event->vendor->admins()->first();

        if ($admin) {
            dispatch(new SendAccountSuspendedJob($admin, $event->reason));
        }
    }
}
