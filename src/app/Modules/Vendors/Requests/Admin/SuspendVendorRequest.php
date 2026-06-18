<?php

namespace App\Modules\Vendors\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SuspendVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'super_admin'], true);
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }
}
