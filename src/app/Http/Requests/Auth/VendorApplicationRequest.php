<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class VendorApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'              => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()->symbols()],
            'business_name'         => ['required', 'string', 'max:255'],
            'phone'                 => ['required', 'string', 'max:30'],
            'address'               => ['required', 'string', 'max:500'],
            'description'           => ['nullable', 'string', 'max:1000'],
            'business_registration' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'business_name'         => 'business name',
            'business_registration' => 'business registration number',
        ];
    }
}
