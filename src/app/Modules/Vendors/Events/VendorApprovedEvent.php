<?php

namespace App\Modules\Vendors\Events;

use App\Models\Vendor;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VendorApprovedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Vendor $vendor) {}
}
