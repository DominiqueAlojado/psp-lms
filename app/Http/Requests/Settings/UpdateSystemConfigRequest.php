<?php

namespace App\Http\Requests\Settings;

use App\Models\SystemConfig;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'configs' => ['required', 'array', 'min:1'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                $editableConfigs = SystemConfig::query()
                    ->where('is_editable', true)
                    ->pluck('type', 'key');

                foreach ((array) $this->input('configs', []) as $key => $value) {
                    if (! $editableConfigs->has($key)) {
                        $validator->errors()->add('configs', "Unknown config key [$key].");
                        continue;
                    }

                    $isValid = match ($editableConfigs[$key]) {
                        'boolean' => is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false'], true),
                        'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false,
                        'number' => is_numeric($value),
                        'json' => is_array($value),
                        'text', 'string' => is_null($value) || is_string($value) || is_numeric($value),
                        default => true,
                    };

                    if (! $isValid) {
                        $validator->errors()->add(
                            'configs',
                            "Invalid value provided for [$key].",
                        );
                    }
                }
            },
        ];
    }
}
