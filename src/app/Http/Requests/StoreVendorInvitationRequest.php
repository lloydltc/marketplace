<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreVendorInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isVendorAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'email'         => ['required', 'string', 'email', 'max:255'],
            'temp_password' => ['required', 'string', Password::min(8)],
        ];
    }
}
