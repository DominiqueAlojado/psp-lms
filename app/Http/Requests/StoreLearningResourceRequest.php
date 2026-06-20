<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLearningResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'max:255'],
            'target_year_levels' => ['nullable', 'array'],
            'target_year_levels.*' => ['string'],
            'is_published' => ['nullable', 'boolean'],
            'file' => ['required', 'file', 'max:51200', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,mp4,mp3,jpg,jpeg,png,gif,txt,zip'],
        ];
    }
}
