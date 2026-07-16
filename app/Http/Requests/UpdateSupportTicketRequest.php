<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['open', 'in_review', 'resolved'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
