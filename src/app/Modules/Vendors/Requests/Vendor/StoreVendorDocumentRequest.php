<?php

namespace App\Modules\Vendors\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class StoreVendorDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vendor = $this->attributes->get('vendor');

        return $vendor && $this->user()?->can('uploadDocument', $vendor);
    }

    public function rules(): array
    {
        return [
            'document_type' => [
                'required',
                'in:business_registration,tax_id,bank_proof,id_copy,address_proof',
            ],
            'document' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'document.mimes' => 'Documents must be PDF, JPG, or PNG format.',
            'document.max'   => 'Document file size must not exceed 5MB.',
        ];
    }
}
