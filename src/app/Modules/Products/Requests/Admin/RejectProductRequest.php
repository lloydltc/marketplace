<?php

namespace App\Modules\Products\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RejectProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reject', \App\Modules\Products\Models\Product::class);
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
