<?php

namespace App\Services;

use App\Models\SystemConfig;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SystemConfigReadService
{
    public function indexPayload(User $user): array
    {
        $this->authorize($user);

        $configs = SystemConfig::query()
            ->orderBy('module')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        return [
            'configurations' => $configs->map(fn (SystemConfig $config) => [
                'id' => $config->id,
                'key' => $config->key,
                'module' => $config->module,
                'label' => $config->label,
                'description' => $config->description,
                'type' => $config->type,
                'value' => $this->castValue($config),
                'is_public' => $config->is_public,
                'is_editable' => $config->is_editable,
                'sort_order' => $config->sort_order,
            ])->values(),
            'summary' => [
                'total' => $configs->count(),
                'public' => $configs->where('is_public', true)->count(),
                'modules' => $configs->pluck('module')->unique()->count(),
            ],
        ];
    }

    public function publicPayload(): array
    {
        $configs = SystemConfig::query()
            ->where('is_public', true)
            ->orderBy('module')
            ->orderBy('sort_order')
            ->get();

        return $this->toNestedPayload($configs);
    }

    public function authorize(User $user): void
    {
        if (! $user->hasPermissionTo('manage-system-configurations')) {
            abort(403, 'You do not have permission to manage system configurations.');
        }
    }

    public function castValue(SystemConfig $config): mixed
    {
        return match ($config->type) {
            'boolean' => filter_var($config->value, FILTER_VALIDATE_BOOL),
            'integer' => $config->value === null ? null : (int) $config->value,
            'number' => $config->value === null ? null : (float) $config->value,
            'json' => $config->value ? json_decode($config->value, true) : null,
            default => $config->value,
        };
    }

    private function toNestedPayload(Collection $configs): array
    {
        $payload = [];

        foreach ($configs as $config) {
            $segments = collect(explode('.', $config->key))
                ->map(fn (string $segment) => Str::camel($segment))
                ->values()
                ->all();

            $cursor = &$payload;

            foreach ($segments as $index => $segment) {
                if ($index === count($segments) - 1) {
                    $cursor[$segment] = $this->castValue($config);
                    continue;
                }

                if (! isset($cursor[$segment]) || ! is_array($cursor[$segment])) {
                    $cursor[$segment] = [];
                }

                $cursor = &$cursor[$segment];
            }

            unset($cursor);
        }

        return $payload;
    }
}
