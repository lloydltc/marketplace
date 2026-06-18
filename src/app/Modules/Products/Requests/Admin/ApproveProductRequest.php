<?php

namespace App\Modules\Products\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApproveProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('approve', \App\Modules\Products\Models\Product::class);
    }

    public function rules(): array
    {
        return [];
    }
}
