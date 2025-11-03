# ExportButton Component

A reusable React component for adding Excel export functionality to any page.

## Basic Usage

```tsx
import { ExportButton } from '@/components/export-button';

<ExportButton
    exportUrl="/residents/export"
    filters={{ search: 'john', status: 'active' }}
/>
```

## Props

| Prop | Type | Default | Required | Description |
|------|------|---------|----------|-------------|
| `exportUrl` | string | - | ✅ | The export route/URL |
| `filters` | object | `{}` | ❌ | Filters to include in export |
| `buttonText` | string | `'Export to Excel'` | ❌ | Button label |
| `variant` | string | `'outline'` | ❌ | Button variant |
| `showIcon` | boolean | `true` | ❌ | Show download icon |
| `icon` | Component | `Download` | ❌ | Custom icon component |
| `successMessage` | string | `'Exporting data...'` | ❌ | Toast message |
| `className` | string | - | ❌ | Additional CSS classes |
| `onExport` | function | - | ❌ | Callback function |

## Examples

### With filters
```tsx
<ExportButton
    exportUrl="/residents/export"
    filters={filters}
    successMessage="Exporting residents..."
/>
```

### Custom styling
```tsx
<ExportButton
    exportUrl="/users/export"
    filters={filters}
    buttonText="Download Users"
    variant="default"
    className="w-full"
/>
```

### Custom icon
```tsx
import { FileSpreadsheet } from 'lucide-react';

<ExportButton
    exportUrl="/reports/export"
    icon={FileSpreadsheet}
    buttonText="Export Report"
/>
```

### With callback
```tsx
<ExportButton
    exportUrl="/data/export"
    filters={filters}
    onExport={() => console.log('Export started')}
/>
```

## How It Works

1. Component receives `exportUrl` and `filters`
2. When clicked, it builds a query string from filters
3. Triggers download by setting `window.location.href`
4. Shows a success toast notification
5. Optionally calls `onExport` callback

## See Also

- Backend setup: `docs/EXPORT_GUIDE.md`
- Quick example: `docs/EXPORT_EXAMPLE.md`
- Base export class: `app/Exports/BaseExport.php`

