<?php

namespace App\Modules\Vehicles\Requests\Vendor;

use App\Modules\Vehicles\Requests\VehicleRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleRequest extends FormRequest
{
    use VehicleRules;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('vehicle'));
    }

    public function rules(): array
    {
        $rules = $this->vehicleRules();

        // Allow the current vehicle's own VIN when updating
        $vehicleId = $this->route('vehicle')?->id;
        if ($vehicleId) {
            $rules['vin'] = ['nullable', 'string', 'size:17', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/i',
                "unique:vehicles,vin,{$vehicleId}"];
        }

        return $rules;
    }

    public function messages(): array
    {
        return $this->vehicleMessages();
    }
}
