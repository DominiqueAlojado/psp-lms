# Quick Export Example

This is a complete example showing how to add Excel export to a "Users" page.

## 1. Create Export Class (2 minutes)

**File:** `app/Exports/UsersExport.php`

```php
<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UsersExport extends BaseExport
{
    public function query(): Builder
    {
        $query = User::query()->with(['roles', 'organizations']);

        // Apply filters using helper methods
        $this->applySearch($query);
        $this->applyFilter($query, 'status', 'status');
        $this->applyFilter($query, 'role', 'role_id');

        return $query->orderBy('name', 'asc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Email',
            'Role',
            'Status',
            'Created At',
        ];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->email,
            $user->roles->pluck('name')->join(', ') ?: 'N/A',
            ucfirst($user->status ?? 'active'),
            $user->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
```

## 2. Add Controller Method (1 minute)

**File:** `app/Http/Controllers/UserController.php`

```php
use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Export users to Excel.
 */
public function export(Request $request): BinaryFileResponse
{
    $filters = $request->only(['search', 'status', 'role']);
    
    return Excel::download(
        new UsersExport($filters),
        'users_' . now()->format('Y-m-d_His') . '.xlsx'
    );
}
```

## 3. Add Route (30 seconds)

**File:** `routes/web.php`

```php
Route::get('users/export', [UserController::class, 'export'])
    ->name('users.export');
```

## 4. Add Button to UI (30 seconds)

**File:** `resources/js/pages/users/index.tsx`

```tsx
import { ExportButton } from '@/components/export-button';

export default function UsersPage({ users, filters }) {
    return (
        <div>
            {/* Your existing header */}
            <div className="flex gap-2">
                <ExportButton
                    exportUrl="/users/export"
                    filters={filters}
                />
                <Button onClick={handleAdd}>Add User</Button>
            </div>
            
            {/* Your existing table */}
        </div>
    );
}
```

## Done! 🎉

Total time: ~4 minutes

Now your users page has a fully functional Excel export that:
- ✅ Respects all active filters
- ✅ Includes proper column headers
- ✅ Formats data nicely
- ✅ Has consistent UI/UX
- ✅ Shows success toast
- ✅ Downloads with timestamp filename

## Customize (Optional)

### Change button text and style:
```tsx
<ExportButton
    exportUrl="/users/export"
    filters={filters}
    buttonText="Download Users"
    variant="default"
/>
```

### Add custom icon:
```tsx
import { Users } from 'lucide-react';

<ExportButton
    exportUrl="/users/export"
    filters={filters}
    icon={Users}
    buttonText="Export Users"
/>
```

### Add callback:
```tsx
<ExportButton
    exportUrl="/users/export"
    filters={filters}
    onExport={() => trackEvent('users_exported')}
/>
```

## That's it!

The reusable components handle everything else:
- Building query strings
- Applying filters
- Triggering downloads
- Showing notifications
- Error handling

