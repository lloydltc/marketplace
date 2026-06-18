<?php

namespace App\Modules\Vehicles\Requests\PrivateSeller;

use App\Modules\Vehicles\Requests\VehicleRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    use VehicleRules;

    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Modules\Vehicles\Models\Vehicle::class);
    }

    public function rules(): array
    {
        return array_merge($this->vehicleRules(), $this->imageUploadRules());
    }

    public function messages(): array
    {
        return $this->vehicleMessages();
    }
}
