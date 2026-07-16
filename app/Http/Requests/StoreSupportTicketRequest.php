<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(['bug', 'billing', 'content', 'account', 'feature'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
            'module_name' => ['nullable', 'string', 'max:255'],
            'page_url' => ['nullable', 'string', 'max:255'],
            'details' => ['required', 'string', 'max:5000'],
        ];
    }
}
