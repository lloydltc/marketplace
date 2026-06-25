<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * PM0: seeds the lookup tables (transmissions, common engines) and a few
 * illustrative generations for top Zimbabwe-market models. Runs after
 * VehicleMakeSeeder. Idempotent (insertOrIgnore on unique keys).
 */
class VehicleTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTransmissions();
        $this->seedEngines();
        $this->seedGenerations();
    }

    private function seedTransmissions(): void
    {
        foreach (['manual', 'automatic', 'cvt', 'dct'] as $type) {
            DB::table('vehicle_transmissions')->insertOrIgnore([
                'id' => (string) Str::uuid(), 'type' => $type, 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function seedEngines(): void
    {
        $engines = [
            ['2.8 GD-6', '2.8L', 'diesel'],
            ['2.4 GD-6', '2.4L', 'diesel'],
            ['1.8 VVT-i', '1.8L', 'petrol'],
            ['2.0 TDI', '2.0L', 'diesel'],
            ['3.0 D-4D', '3.0L', 'diesel'],
            ['1.5 dCi', '1.5L', 'diesel'],
            ['2.5 DiD', '2.5L', 'diesel'],
            ['1.6 petrol', '1.6L', 'petrol'],
        ];

        foreach ($engines as [$code, $displacement, $fuel]) {
            DB::table('vehicle_engines')->insertOrIgnore([
                'id' => (string) Str::uuid(), 'code' => $code, 'displacement' => $displacement,
                'fuel_type' => $fuel, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function seedGenerations(): void
    {
        // model name => [ [generation, year_start, year_end], ... ]
        $byModel = [
            'Hilux'   => [['Vigo', 2005, 2015], ['Revo', 2015, 2024]],
            'Corolla' => [['E140/E150', 2006, 2013], ['E170', 2013, 2019], ['E210', 2019, 2024]],
            'Ranger'  => [['T6', 2011, 2022], ['Next-Gen', 2022, 2024]],
            'Navara'  => [['D40', 2005, 2015], ['NP300/D23', 2015, 2024]],
            'D-Max'   => [['1st Gen', 2012, 2020], ['2nd Gen', 2020, 2024]],
        ];

        foreach ($byModel as $modelName => $generations) {
            $modelIds = DB::table('vehicle_models')->where('name', $modelName)->pluck('id');

            foreach ($modelIds as $modelId) {
                foreach ($generations as [$name, $start, $end]) {
                    $exists = DB::table('vehicle_generations')
                        ->where('model_id', $modelId)->where('name', $name)->exists();
                    if ($exists) {
                        continue;
                    }
                    DB::table('vehicle_generations')->insert([
                        'id' => (string) Str::uuid(), 'model_id' => $modelId, 'name' => $name,
                        'year_start' => $start, 'year_end' => $end, 'is_active' => true,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
