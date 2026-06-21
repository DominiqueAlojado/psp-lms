<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DetachResidentOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'exists:organizations,id'],
        ];
    }
}
