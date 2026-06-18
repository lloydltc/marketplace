<?php

namespace App\Modules\Vendors\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vendor = $this->attributes->get('vendor');

        return $vendor && $this->user()?->can('manageBankAccounts', $vendor);
    }

    public function rules(): array
    {
        return [
            'bank_name'      => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'max:30'],
            'account_holder' => ['required', 'string', 'max:200'],
            'branch_code'    => ['nullable', 'string', 'max:20'],
        ];
    }
}
