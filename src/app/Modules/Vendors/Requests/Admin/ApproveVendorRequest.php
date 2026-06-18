<?php

namespace App\Modules\Vendors\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApproveVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'super_admin'], true);
    }

    public function rules(): array
    {
        return [];
    }
}
