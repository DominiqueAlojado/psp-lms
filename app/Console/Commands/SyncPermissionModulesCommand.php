<?php

namespace App\Console\Commands;

use App\Services\RolesPermissionsManagementService;
use Illuminate\Console\Command;

class SyncPermissionModulesCommand extends Command
{
    protected $signature = 'permissions:sync-modules';

    protected $description = 'Sync permission modules from existing permission module values';

    public function handle(RolesPermissionsManagementService $managementService): int
    {
        $synced = $managementService->syncPermissionModulesFromPermissions();

        $this->info("Synced {$synced} permission module entries from permissions.");

        return self::SUCCESS;
    }
}
