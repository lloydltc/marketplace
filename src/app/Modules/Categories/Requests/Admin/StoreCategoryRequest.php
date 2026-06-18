<?php

namespace App\Modules\Categories\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'super_admin'], true);
    }

    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'min:2', 'max:100'],
            'parent_id'           => ['nullable', 'uuid', 'exists:categories,id'],
            'description'         => ['nullable', 'string', 'max:1000'],
            'icon'                => ['nullable', 'string', 'max:50'],
            'commission_override' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sort_order'          => ['nullable', 'integer', 'min:0'],
        ];
    }
}
