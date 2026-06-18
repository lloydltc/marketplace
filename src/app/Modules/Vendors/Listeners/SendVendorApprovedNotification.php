<?php

namespace App\Modules\Vendors\Listeners;

use App\Jobs\Mail\SendVendorApprovedJob;
use App\Modules\Vendors\Events\VendorApprovedEvent;

class SendVendorApprovedNotification
{
    public function handle(VendorApprovedEvent $event): void
    {
        dispatch(new SendVendorApprovedJob($event->vendor));
    }
}
