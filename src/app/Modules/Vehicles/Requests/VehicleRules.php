<?php

namespace App\Modules\Vehicles\Requests;

use App\Modules\Vehicles\Services\VehicleConditionService;

trait VehicleRules
{
    protected function vehicleRules(): array
    {
        $currentYear = (int) date('Y');

        return [
            'make_id'      => ['required', 'uuid', 'exists:vehicle_makes,id'],
            'model_id'     => ['required', 'uuid', 'exists:vehicle_models,id'],
            'year'         => ['required', 'integer', 'min:1900', 'max:' . ($currentYear + 1)],
            'body_type'    => ['required', 'string', 'in:' . implode(',', VehicleConditionService::BODY_TYPES)],
            'transmission' => ['required', 'string', 'in:' . implode(',', VehicleConditionService::TRANSMISSIONS)],
            'fuel_type'    => ['required', 'string', 'in:' . implode(',', VehicleConditionService::FUEL_TYPES)],
            'engine_cc'    => ['nullable', 'integer', 'min:50', 'max:20000'],
            'mileage'      => ['required', 'integer', 'min:0'],
            'vin'          => ['nullable', 'string', 'size:17', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/i', 'unique:vehicles,vin'],
            'color'        => ['required', 'string', 'max:50'],
            'condition'    => ['required', 'string', 'in:' . implode(',', VehicleConditionService::CONDITIONS)],
            'price_zwl'    => ['required', 'numeric', 'min:1', 'max:999999999.99'],
            'price_usd'    => ['nullable', 'numeric', 'min:1', 'max:9999999.99'],
            'description'  => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * Optional create-time image rules. First validation layer; the secure
     * pipeline + per-tier count limits are enforced in ImageUploadService.
     */
    protected function imageUploadRules(): array
    {
        return [
            'images'   => ['nullable', 'array', 'max:20'],
            'images.*' => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
        ];
    }

    protected function vehicleMessages(): array
    {
        return [
            'vin.regex' => 'VIN must be 17 alphanumeric characters (letters I, O, Q not allowed).',
            'vin.size'  => 'VIN must be exactly 17 characters.',
        ];
    }
}
