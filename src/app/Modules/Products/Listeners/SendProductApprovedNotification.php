<?php

namespace App\Modules\Products\Listeners;

use App\Mail\ProductApprovedMailable;
use App\Modules\Products\Events\ProductApprovedEvent;
use Illuminate\Support\Facades\Mail;

class SendProductApprovedNotification
{
    public function handle(ProductApprovedEvent $event): void
    {
        $product = $event->product->load('vendor.admins');

        foreach ($product->vendor->admins as $admin) {
            Mail::to($admin->email)->queue(new ProductApprovedMailable($product));
        }
    }
}
