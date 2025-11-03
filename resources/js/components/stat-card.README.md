# StatCard Component

A reusable card component for displaying statistics and metrics.

## Basic Usage

```tsx
import { StatCard } from '@/components/stat-card';
import { Users } from 'lucide-react';

<StatCard
    title="Total Users"
    value={1234}
    icon={Users}
/>
```

## Props

| Prop | Type | Default | Required | Description |
|------|------|---------|----------|-------------|
| `title` | string | - | ✅ | Card title/label |
| `value` | string \| number | - | ✅ | Main value to display |
| `description` | string | - | ❌ | Subtitle or additional context |
| `icon` | LucideIcon | - | ❌ | Icon component from lucide-react |
| `iconColor` | string | `'text-muted-foreground'` | ❌ | Icon color classes |
| `trend` | string | - | ❌ | Trend indicator (e.g., "+12%") |
| `trendDirection` | `'up' \| 'down' \| 'neutral'` | `'neutral'` | ❌ | Trend direction for color |
| `className` | string | - | ❌ | Additional CSS classes |
| `onClick` | function | - | ❌ | Click handler (makes card interactive) |

## Examples

### Simple stat card
```tsx
<StatCard
    title="Active Users"
    value={42}
/>
```

### With icon
```tsx
import { Users } from 'lucide-react';

<StatCard
    title="Total Residents"
    value={156}
    icon={Users}
/>
```

### With description
```tsx
<StatCard
    title="First Year"
    value={23}
    description="residents"
    icon={Users}
/>
```

### With trend indicator
```tsx
<StatCard
    title="Monthly Sign-ups"
    value={145}
    trend="+12.5%"
    trendDirection="up"
    icon={TrendingUp}
/>
```

### Interactive (clickable)
```tsx
<StatCard
    title="Pending Tasks"
    value={8}
    description="tasks"
    onClick={() => router.visit('/tasks?status=pending')}
/>
```

### Custom styling
```tsx
<StatCard
    title="Critical Alerts"
    value={3}
    iconColor="text-red-500"
    className="border-red-200"
    icon={AlertTriangle}
/>
```

### Highlighted/Active state
```tsx
<StatCard
    title="Selected Category"
    value={45}
    iconColor="text-primary"
    className="border-primary"
    icon={Filter}
/>
```

## Real-World Example: Year Level Statistics

```tsx
const yearLevelStats = {
    'First Year': 23,
    'Second Year': 34,
    'Third Year': 28,
    'Fourth Year': 31,
};

<div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
    {Object.entries(yearLevelStats).map(([level, count]) => (
        <StatCard
            key={level}
            title={level}
            value={count}
            description={`${count === 1 ? 'student' : 'students'}`}
            icon={Users}
            onClick={() => filterByYearLevel(level)}
        />
    ))}
</div>
```

## Responsive Grid Layouts

### 2 columns on mobile, 4 on desktop
```tsx
<div className="grid gap-4 grid-cols-2 lg:grid-cols-4">
    {/* cards */}
</div>
```

### 1 column on mobile, 3 on tablet, 6 on desktop
```tsx
<div className="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
    {/* cards */}
</div>
```

## Styling Tips

### Hover effect (already included if onClick is provided)
The component automatically adds hover effects when `onClick` is provided.

### Custom hover
```tsx
<StatCard
    title="Custom Hover"
    value={42}
    className="hover:shadow-lg transition-shadow"
/>
```

### Dark mode support
All colors automatically support dark mode via Tailwind's `dark:` variants.

## Accessibility

- Cards with `onClick` are automatically keyboard accessible
- Semantic HTML structure with proper heading hierarchy
- Color contrast meets WCAG standards

## See Also

- Example usage: `resources/js/pages/residents/index.tsx`
- Card component: `@/components/ui/card`

