<?php

namespace App\Modules\Vehicles\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RejectVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reject', \App\Modules\Vehicles\Models\Vehicle::class);
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
