<?php

namespace App\Modules\Products\Listeners;

use App\Mail\ProductRejectedMailable;
use App\Modules\Products\Events\ProductRejectedEvent;
use Illuminate\Support\Facades\Mail;

class SendProductRejectedNotification
{
    public function handle(ProductRejectedEvent $event): void
    {
        $product = $event->product->load('vendor.admins');

        foreach ($product->vendor->admins as $admin) {
            Mail::to($admin->email)->queue(new ProductRejectedMailable($product, $event->reason));
        }
    }
}
