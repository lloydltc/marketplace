<?php

namespace App\Modules\Vehicles\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApproveVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('approve', \App\Modules\Vehicles\Models\Vehicle::class);
    }

    public function rules(): array
    {
        return [];
    }
}
