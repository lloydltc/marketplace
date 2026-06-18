<?php

namespace Database\Factories;

use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        $make  = VehicleMake::inRandomOrder()->first();
        $model = $make ? VehicleModel::where('make_id', $make->id)->inRandomOrder()->first() : null;

        return [
            'vendor_id'    => null,
            'user_id'      => null,
            'make_id'      => $make?->id,
            'model_id'     => $model?->id,
            'year'         => fake()->numberBetween(2000, 2025),
            'body_type'    => fake()->randomElement(['sedan', 'hatchback', 'suv', 'pickup', 'van']),
            'transmission' => fake()->randomElement(['manual', 'automatic']),
            'fuel_type'    => fake()->randomElement(['petrol', 'diesel', 'hybrid']),
            'engine_cc'    => fake()->optional(0.7)->numberBetween(1000, 4000),
            'mileage'      => fake()->numberBetween(0, 300000),
            'vin'          => null,
            'color'        => fake()->safeColorName(),
            'condition'    => 'used',
            'status'       => 'pending',
            'price_zwl'    => fake()->randomFloat(2, 500000, 50000000),
            'price_usd'    => fake()->optional(0.6)->randomFloat(2, 500, 50000),
            'description'  => fake()->optional(0.5)->paragraph(),
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function rejected(): static
    {
        return $this->state(['status' => 'rejected']);
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }

    public function forVendor(\App\Models\Vendor $vendor): static
    {
        return $this->state(['vendor_id' => $vendor->id, 'user_id' => null]);
    }

    public function forSeller(\App\Models\User $user): static
    {
        return $this->state(['vendor_id' => null, 'user_id' => $user->id]);
    }

    public function newVehicle(): static
    {
        return $this->state(['condition' => 'new', 'mileage' => 0]);
    }

    public function withVin(): static
    {
        return $this->state(fn () => ['vin' => strtoupper(fake()->bothify('???############'))]);
    }
}
