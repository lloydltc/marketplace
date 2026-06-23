<?php

namespace App\Modules\Vehicles\Requests;

use App\Modules\Vehicles\Services\VehicleConditionService;

trait VehicleRules
{
    /**
     * H1: when saving a DRAFT, required fields relax to nullable so partial input
     * persists. Format/type rules still apply. Publishing uses the full rules.
     */
    protected function vehicleRules(bool $draft = false): array
    {
        $currentYear = (int) date('Y');
        $req = $draft ? 'nullable' : 'required';

        return [
            // H0: listing type (cars/bikes/boats/trailers). Absent → DB default 'vehicle'.
            'vehicle_type' => ['nullable', 'string', 'in:' . implode(',', \App\Modules\Vehicles\Models\Vehicle::types())],
            'make_id'      => [$req, 'uuid', 'exists:vehicle_makes,id'],
            'model_id'     => [$req, 'uuid', 'exists:vehicle_models,id'],
            'year'         => [$req, 'integer', 'min:1900', 'max:' . ($currentYear + 1)],
            // Body type validated against the union of all types' sets (form shows only the relevant ones).
            'body_type'    => [$req, 'string', 'in:' . implode(',', \App\Modules\Vehicles\Models\Vehicle::allBodyTypes())],
            'transmission' => [$req, 'string', 'in:' . implode(',', VehicleConditionService::TRANSMISSIONS)],
            'fuel_type'    => [$req, 'string', 'in:' . implode(',', VehicleConditionService::FUEL_TYPES)],
            'engine_cc'    => ['nullable', 'integer', 'min:50', 'max:20000'],
            'mileage'      => [$req, 'integer', 'min:0'],
            'vin'          => ['nullable', 'string', 'size:17', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/i', 'unique:vehicles,vin'],
            'color'        => [$req, 'string', 'max:50'],
            'condition'    => [$req, 'string', 'in:' . implode(',', VehicleConditionService::CONDITIONS)],
            // Either currency is acceptable — sellers aren't forced to use ZWL,
            // but at least one price must be given (when publishing).
            'price_zwl'    => $draft ? ['nullable', 'numeric', 'min:1', 'max:999999999.99'] : ['nullable', 'required_without:price_usd', 'numeric', 'min:1', 'max:999999999.99'],
            'price_usd'    => $draft ? ['nullable', 'numeric', 'min:1', 'max:9999999.99'] : ['nullable', 'required_without:price_zwl', 'numeric', 'min:1', 'max:9999999.99'],
            'description'  => ['nullable', 'string', 'max:5000'],
            // H2: Zimbabwe-market fields (optional even on publish).
            'show_price'       => ['nullable', 'boolean'],
            'duty_paid'        => ['nullable', 'boolean'],
            'is_recent_import' => ['nullable', 'boolean'],
            'ref_code'         => ['nullable', 'string', 'max:40'],
            'steering'         => ['nullable', 'in:lhd,rhd'],
            'action'           => ['nullable', 'in:draft,publish'],
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

    /** Dynamic feature values (D4): features[<definition_id>] => value. */
    protected function featureRules(): array
    {
        return [
            'features'   => ['nullable', 'array'],
            'features.*' => ['nullable', 'string', 'max:255'],
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
