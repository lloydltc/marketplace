<?php

namespace App\Modules\Vendors\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVendorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vendor = $this->attributes->get('vendor');

        return $vendor && $this->user()?->can('update', $vendor);
    }

    public function rules(): array
    {
        return [
            'name'                  => ['sometimes', 'string', 'min:2', 'max:200'],
            'contact_email'         => ['sometimes', 'email', 'max:255'],
            'phone'                 => ['sometimes', 'nullable', 'string', 'max:20'],
            'address'               => ['sometimes', 'nullable', 'string', 'max:500'],
            'description'           => ['sometimes', 'nullable', 'string', 'max:2000'],
            'business_registration' => ['sometimes', 'nullable', 'string', 'max:100'],
            'tax_id'                => ['sometimes', 'nullable', 'string', 'max:50'],
        ];
    }
}
