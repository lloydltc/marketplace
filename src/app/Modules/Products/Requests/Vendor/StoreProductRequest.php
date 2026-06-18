<?php

namespace App\Modules\Products\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Modules\Products\Models\Product::class);
    }

    public function rules(): array
    {
        return [
            'category_id'  => ['required', 'uuid', 'exists:categories,id'],
            'title'        => ['required', 'string', 'min:5', 'max:200'],
            'description'  => ['required', 'string', 'min:20'],
            'sku'          => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-_]+$/'],
            'price_zwl'    => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'price_usd'    => ['nullable', 'numeric', 'min:0.01', 'max:9999999.99'],
            'quantity'     => ['required', 'integer', 'min:0'],

            // Optional create-time images (the secure pipeline + tier limits are
            // enforced in ImageUploadService; these are the first validation layer).
            'images'   => ['nullable', 'array', 'max:20'],
            'images.*' => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.regex' => 'SKU may only contain letters, numbers, hyphens, and underscores.',
        ];
    }
}
