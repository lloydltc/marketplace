<?php

namespace Database\Seeders;

use App\Modules\Vehicles\Models\FeatureDefinition;
use Illuminate\Database\Seeder;

/**
 * D4: a sensible starter set of vehicle features. Admins can add/edit/retire more
 * at runtime — nothing here is hardcoded in application logic.
 */
class FeatureDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            // group, name, key, type, unit, options, filterable, order
            ['Key specs', 'Doors', 'doors', 'number', 'doors', null, true, 1],
            ['Key specs', 'Seats', 'seats', 'number', 'seats', null, true, 2],
            ['Key specs', 'Drivetrain', 'drivetrain', 'enum', null, ['FWD', 'RWD', 'AWD', '4WD'], true, 3],
            ['Key specs', 'Engine size', 'engine_size', 'number', 'L', null, false, 4],

            ['Comfort', 'Air conditioning', 'air_conditioning', 'boolean', null, null, true, 1],
            ['Comfort', 'Sunroof', 'sunroof', 'boolean', null, null, true, 2],
            ['Comfort', 'Leather seats', 'leather_seats', 'boolean', null, null, false, 3],
            ['Comfort', 'Bluetooth', 'bluetooth', 'boolean', null, null, false, 4],
            ['Comfort', 'Radio', 'radio', 'boolean', null, null, false, 5],

            ['Safety', 'Parking sensors', 'parking_sensors', 'boolean', null, null, true, 1],
            ['Safety', 'Reverse camera', 'reverse_camera', 'boolean', null, null, true, 2],
            ['Safety', 'ABS', 'abs', 'boolean', null, null, false, 3],
            ['Safety', 'Airbags', 'airbags', 'number', null, null, false, 4],
        ];

        foreach ($features as [$group, $name, $key, $type, $unit, $options, $filterable, $order]) {
            FeatureDefinition::updateOrCreate(
                ['key' => $key],
                [
                    'name' => $name, 'type' => $type, 'unit' => $unit, 'options' => $options,
                    'is_filterable' => $filterable, 'group' => $group, 'sort_order' => $order,
                    'is_active' => true,
                ],
            );
        }
    }
}
