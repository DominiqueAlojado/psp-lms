# Activity Log Services

This directory contains activity log services, where each controller that needs activity logging has its own dedicated service class.

## Structure

Each controller should have its own activity log service following this pattern:

- **Controller**: `StaffController`
- **Service**: `StaffActivityLogService`
- **Location**: `app/Services/ActivityLog/StaffActivityLogService.php`

## Pattern

### Service Naming Convention
- Format: `{ControllerName}ActivityLogService`
- Example: `StaffController` → `StaffActivityLogService`
- Example: `OrganizationController` → `OrganizationActivityLogService`

### Service Responsibilities
Each service should handle:
1. **Logging operations** for its specific controller/domain
   - `log{Entity}Created()` - Log creation
   - `log{Entity}Updated()` - Log updates
   - `log{Entity}Deleted()` - Log deletions
2. **Retrieving logs** for its specific entity
   - `getLogs()` - Get activity logs for an entity
3. **Helper methods** for building log data
   - `buildUpdateLogData()` - Build consolidated update log data

### Controller Usage

```php
use App\Services\ActivityLog\StaffActivityLogService;
use App\Traits\LogsActivity;

class StaffController extends Controller
{
    use LogsActivity; // Only for helper methods like withoutActivityLogging()

    public function __construct(
        protected StaffActivityLogService $activityLogService
    ) {
    }

    public function store(Request $request): RedirectResponse
    {
        $user = User::create([...]);
        
        // Use the service to log
        $this->activityLogService->logUserCreated($user);
        
        return back()->with('success', 'Created');
    }

    public function logs(User $staff): JsonResponse
    {
        $logs = $this->activityLogService->getLogs($staff);
        return response()->json(['logs' => $logs]);
    }
}
```

## LogsActivity Trait

The `LogsActivity` trait in `app/Traits/LogsActivity.php` provides **only** common utility methods:
- `withoutActivityLogging()` - Temporarily disable logging
- `logActivity()` - Generic logging helper (optional)

**Important**: The trait is NOT where controller-specific logging logic goes. Each controller should have its own service class.

## Adding a New Activity Log Service

1. Create a new service file: `app/Services/ActivityLog/{ControllerName}ActivityLogService.php`
2. Implement the logging methods for that controller's domain
3. Inject the service into the controller's constructor
4. Use the service methods in your controller actions

### Example: Creating ResidentActivityLogService

```php
<?php

namespace App\Services\ActivityLog;

use App\Models\Resident;
use Spatie\Activitylog\Models\Activity as ActivityLog;

class ResidentActivityLogService
{
    public function logResidentCreated(Resident $resident): void
    {
        activity()
            ->performedOn($resident)
            ->causedBy(auth()->user() ?? null)
            ->useLog('residents')
            ->log('Resident created');
    }

    public function getLogs(Resident $resident, int $limit = 100): array
    {
        return ActivityLog::query()
            ->where('subject_type', Resident::class)
            ->where('subject_id', $resident->id)
            ->where('log_name', 'residents')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn($activity) => [...])
            ->toArray();
    }
}
```

## Benefits

- **Separation of Concerns**: Each controller's logging logic is isolated
- **Maintainability**: Easy to find and modify logging for specific controllers
- **Testability**: Services can be tested independently
- **Scalability**: Adding new controllers doesn't clutter a single file
- **Type Safety**: Each service is specific to its domain

