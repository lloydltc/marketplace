<?php

namespace App\Modules\Products\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Modules\Products\Models\Product::class);
    }

    /**
     * Products are priced in USD with a seller-set USD→ZWL rate; the ZWL price the
     * engine settles in is derived server-side (never trusted from the client).
     */
    protected function prepareForValidation(): void
    {
        $usd  = $this->input('price_usd');
        $rate = $this->input('exchange_rate');

        if (is_numeric($usd) && is_numeric($rate)) {
            $this->merge(['price_zwl' => round((float) $usd * (float) $rate, 2)]);
        }
    }

    public function rules(): array
    {
        return [
            'category_id'   => ['required', 'uuid', 'exists:categories,id'],
            'title'         => ['required', 'string', 'min:5', 'max:200'],
            'description'   => ['required', 'string', 'min:20'],
            'sku'           => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-_]+$/'],
            'price_usd'     => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'exchange_rate' => ['required', 'numeric', 'min:0.0001', 'max:1000000'],
            'price_zwl'     => ['nullable', 'numeric', 'max:999999999.99'], // derived in prepareForValidation
            'quantity'      => ['required', 'integer', 'min:0'],

            // Optional create-time images (the secure pipeline + tier limits are
            // enforced in ImageUploadService; these are the first validation layer).
            'images'   => ['nullable', 'array', 'max:20'],
            'images.*' => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.regex'             => 'SKU may only contain letters, numbers, hyphens, and underscores.',
            'price_usd.required'    => 'Enter the price in USD.',
            'exchange_rate.required' => 'Enter your USD→ZWL exchange rate.',
        ];
    }
}
