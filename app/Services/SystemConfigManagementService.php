<?php

namespace App\Services;

use App\Models\SystemConfig;
use App\Models\User;

class SystemConfigManagementService
{
    public function __construct(
        private readonly SystemConfigReadService $readService,
    ) {}

    public function updateConfigs(User $user, array $configs): void
    {
        $this->readService->authorize($user);

        $records = SystemConfig::query()
            ->where('is_editable', true)
            ->whereIn('key', array_keys($configs))
            ->get()
            ->keyBy('key');

        foreach ($configs as $key => $value) {
            $record = $records->get($key);

            if (! $record) {
                continue;
            }

            $record->update([
                'value' => $this->serializeValue($record->type, $value),
            ]);
        }
    }

    private function serializeValue(string $type, mixed $value): ?string
    {
        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer', 'number' => $value === null || $value === '' ? null : (string) $value,
            'json' => $value === null ? null : json_encode($value, JSON_THROW_ON_ERROR),
            default => $value === null ? null : (string) $value,
        };
    }
}
