# Excel Export Guide

This guide explains how to implement Excel export functionality in any page of the application using the reusable components.

## Overview

The export system consists of three main parts:
1. **BaseExport** - PHP base class for exports
2. **Export Class** - Specific export implementation extending BaseExport
3. **ExportButton** - React component for the UI

## Quick Start

### Step 1: Create an Export Class

Create a new export class in `app/Exports/`:

```php
<?php

namespace App\Exports;

use App\Models\YourModel;
use Illuminate\Database\Eloquent\Builder;

class YourModelsExport extends BaseExport
{
    public function query(): Builder
    {
        $query = YourModel::query()
            ->with(['relation1', 'relation2']);

        // Apply search filter (if model has search scope)
        $this->applySearch($query);

        // Apply specific filters
        $this->applyFilter($query, 'status', 'status');
        $this->applyFilter($query, 'category', 'category_id');
        
        // Custom filter logic
        if ($this->hasFilter('date_from')) {
            $query->where('created_at', '>=', $this->getFilter('date_from'));
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Email',
            'Status',
            'Created At',
        ];
    }

    public function map($model): array
    {
        return [
            $model->id,
            $model->name,
            $model->email,
            ucfirst($model->status),
            $model->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
```

### Step 2: Add Controller Method

Add an export method to your controller:

```php
use App\Exports\YourModelsExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Export models to Excel.
 */
public function export(Request $request): BinaryFileResponse
{
    $filters = $request->only(['search', 'status', 'category', 'date_from', 'date_to']);
    
    $filename = 'your_models_' . now()->format('Y-m-d_His') . '.xlsx';
    
    return Excel::download(new YourModelsExport($filters), $filename);
}
```

### Step 3: Add Route

Add the export route in `routes/web.php`:

```php
Route::get('your-models/export', [YourModelController::class, 'export'])
    ->name('your-models.export');
```

**Important:** Place the export route **before** the resource route with {id} parameter to avoid conflicts.

```php
// ✅ Correct order
Route::get('your-models/export', [YourModelController::class, 'export']);
Route::get('your-models/{model}', [YourModelController::class, 'show']);

// ❌ Wrong order - export will match {model} parameter
Route::get('your-models/{model}', [YourModelController::class, 'show']);
Route::get('your-models/export', [YourModelController::class, 'export']);
```

### Step 4: Add ExportButton to UI

In your React page component:

```tsx
import { ExportButton } from '@/components/export-button';

export default function YourPage({ filters }) {
    return (
        <div>
            <ExportButton
                exportUrl="/your-models/export"
                filters={filters}
                successMessage="Exporting your data..."
            />
        </div>
    );
}
```

## ExportButton Component Props

### Required Props

- **`exportUrl`** (string): The export route/URL

### Optional Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `filters` | `Record<string, any>` | `{}` | Filters to apply to the export |
| `buttonText` | `string` | `'Export to Excel'` | Button text |
| `variant` | `string` | `'outline'` | Button variant (`default`, `outline`, `secondary`, etc.) |
| `showIcon` | `boolean` | `true` | Show/hide the download icon |
| `icon` | `Component` | `Download` | Custom icon component |
| `successMessage` | `string` | `'Exporting data...'` | Success toast message |
| `className` | `string` | `undefined` | Additional CSS classes |
| `onExport` | `function` | `undefined` | Callback after export is triggered |

## Advanced Examples

### Example 1: Custom Button Styling

```tsx
<ExportButton
    exportUrl="/residents/export"
    filters={filters}
    buttonText="Download Report"
    variant="default"
    className="w-full md:w-auto"
/>
```

### Example 2: Custom Icon

```tsx
import { FileSpreadsheet } from 'lucide-react';

<ExportButton
    exportUrl="/residents/export"
    filters={filters}
    icon={FileSpreadsheet}
    buttonText="Excel Report"
/>
```

### Example 3: No Icon

```tsx
<ExportButton
    exportUrl="/residents/export"
    filters={filters}
    showIcon={false}
    buttonText="Download"
/>
```

### Example 4: With Callback

```tsx
<ExportButton
    exportUrl="/residents/export"
    filters={filters}
    onExport={() => {
        console.log('Export started');
        // Track analytics, etc.
    }}
/>
```

## BaseExport Helper Methods

The `BaseExport` class provides helpful methods:

### `getFilter(key, default)`
Get a filter value or return default:
```php
$status = $this->getFilter('status', 'active');
```

### `hasFilter(key)`
Check if a filter exists and is not empty:
```php
if ($this->hasFilter('date_from')) {
    // Apply date filter
}
```

### `applyFilter(query, key, column, operator)`
Apply a filter to the query:
```php
// Simple equality
$this->applyFilter($query, 'status', 'status');

// Custom operator
$this->applyFilter($query, 'min_age', 'age', '>=');
```

### `applySearch(query, key)`
Apply search filter if model has search scope:
```php
$this->applySearch($query);
// or with custom key
$this->applySearch($query, 'q');
```

## Complex Filter Example

```php
public function query(): Builder
{
    $query = Product::query()
        ->with(['category', 'supplier']);

    // Search
    $this->applySearch($query);

    // Simple filters
    $this->applyFilter($query, 'status', 'status');
    $this->applyFilter($query, 'category_id', 'category_id');

    // Price range
    if ($this->hasFilter('min_price')) {
        $query->where('price', '>=', $this->getFilter('min_price'));
    }
    if ($this->hasFilter('max_price')) {
        $query->where('price', '<=', $this->getFilter('max_price'));
    }

    // Date range
    if ($this->hasFilter('date_from')) {
        $query->where('created_at', '>=', $this->getFilter('date_from'));
    }
    if ($this->hasFilter('date_to')) {
        $query->where('created_at', '<=', $this->getFilter('date_to'));
    }

    // Complex condition
    if ($this->hasFilter('include_inactive')) {
        // Include inactive items
    } else {
        $query->where('is_active', true);
    }

    return $query->orderBy('name', 'asc');
}
```

## Real-World Example: Residents Export

See `app/Exports/ResidentsExport.php` and `resources/js/pages/residents/index.tsx` for a complete working example.

## Testing

Test your export by:

1. Navigate to the page with filters
2. Apply various filter combinations
3. Click the export button
4. Verify the Excel file downloads with correct data
5. Check that filters are applied correctly

## Best Practices

1. ✅ Always use `BaseExport` as the parent class
2. ✅ Apply filters conditionally using helper methods
3. ✅ Include relevant relationships in `with()`
4. ✅ Format data in the `map()` method
5. ✅ Use descriptive column headings
6. ✅ Add timestamp to filename
7. ✅ Handle null values gracefully
8. ✅ Order results logically (by name, date, etc.)

## Troubleshooting

### Export downloads empty file
- Check if filters are too restrictive
- Verify the query returns data in the database

### Missing columns
- Ensure `headings()` and `map()` return same number of items
- Check array keys in `map()` method

### Filters not working
- Verify filter keys match between frontend and backend
- Check if column names are correct
- Use `dd($this->filters)` to debug

### Route not found
- Ensure export route is before resource routes with {id}
- Clear route cache: `php artisan route:clear`

