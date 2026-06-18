<?php

namespace App\Modules\Vehicles\Events;

use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VehicleCreatedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Vehicle $vehicle) {}
}
