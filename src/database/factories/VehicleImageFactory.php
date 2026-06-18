<?php

namespace Database\Factories;

use App\Modules\Media\Models\VehicleImage;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleImageFactory extends Factory
{
    protected $model = VehicleImage::class;

    public function definition(): array
    {
        $vehicleId = Vehicle::factory();

        return [
            'vehicle_id'    => $vehicleId,
            'disk'          => 'public',
            'original_path' => 'vehicles/stub/' . fake()->uuid() . '/original.jpg',
            'display_order' => 0,
        ];
    }
}
