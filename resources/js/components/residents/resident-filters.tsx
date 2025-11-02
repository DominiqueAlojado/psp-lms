import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Filter, Search, X } from 'lucide-react';

interface Organization {
    id: number;
    name: string;
    slug: string;
}

interface Filters {
    search?: string;
    organization_id?: number;
    year_level?: string;
    status?: string;
    course?: string;
}

interface Props {
    search: string;
    setSearch: (value: string) => void;
    showFilters: boolean;
    setShowFilters: (value: boolean) => void;
    filters: Filters;
    localFilters: Filters;
    updateFilter: (key: string, value: string | undefined) => void;
    clearFilters: () => void;
    organizations: Organization[];
    yearLevels: string[];
    statuses: string[];
    courses: string[];
}

export function ResidentFilters({
    search,
    setSearch,
    showFilters,
    setShowFilters,
    filters,
    localFilters,
    updateFilter,
    clearFilters,
    organizations,
    yearLevels,
    statuses,
    courses,
}: Props) {
    const hasActiveFilters = Object.keys(filters).some(
        (key) => filters[key as keyof typeof filters],
    );

    return (
        <Card>
            <CardContent className="space-y-6 p-8">
                <div className="flex gap-4">
                    {/* Search Input */}
                    <div className="relative flex-1">
                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            type="text"
                            placeholder="Search by name, email, or contact number..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="pl-10"
                        />
                    </div>

                    {/* Filter Toggle Button */}
                    <Button
                        variant={showFilters ? 'default' : 'outline'}
                        onClick={() => setShowFilters(!showFilters)}
                    >
                        <Filter className="mr-2 h-4 w-4" />
                        Filters
                        {hasActiveFilters && (
                            <Badge variant="secondary" className="ml-2">
                                {
                                    Object.keys(filters).filter(
                                        (k) => filters[k as keyof typeof filters],
                                    ).length
                                }
                            </Badge>
                        )}
                    </Button>

                    {hasActiveFilters && (
                        <Button variant="ghost" onClick={clearFilters}>
                            <X className="mr-2 h-4 w-4" />
                            Clear
                        </Button>
                    )}
                </div>

                {/* Filter Options */}
                {showFilters && (
                    <div className="grid grid-cols-1 gap-4 border-t pt-4 md:grid-cols-2 lg:grid-cols-4">
                        {/* Organization Filter */}
                        <div>
                            <label className="mb-2 block text-sm font-medium">
                                Organization
                            </label>
                            <select
                                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                value={localFilters.organization_id || ''}
                                onChange={(e) =>
                                    updateFilter(
                                        'organization_id',
                                        e.target.value || undefined,
                                    )
                                }
                            >
                                <option value="">All Organizations</option>
                                {organizations.map((org) => (
                                    <option key={org.id} value={org.id}>
                                        {org.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Year Level Filter */}
                        <div>
                            <label className="mb-2 block text-sm font-medium">
                                Year Level
                            </label>
                            <select
                                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                value={localFilters.year_level || ''}
                                onChange={(e) =>
                                    updateFilter(
                                        'year_level',
                                        e.target.value || undefined,
                                    )
                                }
                            >
                                <option value="">All Year Levels</option>
                                {yearLevels.map((level) => (
                                    <option key={level} value={level}>
                                        {level}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Status Filter */}
                        <div>
                            <label className="mb-2 block text-sm font-medium">
                                Status
                            </label>
                            <select
                                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                value={localFilters.status || ''}
                                onChange={(e) =>
                                    updateFilter(
                                        'status',
                                        e.target.value || undefined,
                                    )
                                }
                            >
                                <option value="">All Statuses</option>
                                {statuses.map((status) => (
                                    <option key={status} value={status}>
                                        {status.charAt(0).toUpperCase() +
                                            status.slice(1)}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Course Filter */}
                        <div>
                            <label className="mb-2 block text-sm font-medium">
                                Course
                            </label>
                            <select
                                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                value={localFilters.course || ''}
                                onChange={(e) =>
                                    updateFilter('course', e.target.value || undefined)
                                }
                            >
                                <option value="">All Courses</option>
                                {courses.map((course) => (
                                    <option key={course} value={course}>
                                        {course}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                )}

                {/* Active Filter Chips */}
                {hasActiveFilters && (
                    <div className="flex flex-wrap gap-2">
                        {filters.search && (
                            <Badge variant="secondary" className="gap-1">
                                Search: {filters.search}
                                <X
                                    className="h-3 w-3 cursor-pointer"
                                    onClick={() => {
                                        setSearch('');
                                        updateFilter('search', undefined);
                                    }}
                                />
                            </Badge>
                        )}
                        {filters.organization_id && (
                            <Badge variant="secondary" className="gap-1">
                                Org:{' '}
                                {
                                    organizations.find(
                                        (o) =>
                                            o.id === Number(filters.organization_id),
                                    )?.name
                                }
                                <X
                                    className="h-3 w-3 cursor-pointer"
                                    onClick={() =>
                                        updateFilter('organization_id', undefined)
                                    }
                                />
                            </Badge>
                        )}
                        {filters.year_level && (
                            <Badge variant="secondary" className="gap-1">
                                Year: {filters.year_level}
                                <X
                                    className="h-3 w-3 cursor-pointer"
                                    onClick={() =>
                                        updateFilter('year_level', undefined)
                                    }
                                />
                            </Badge>
                        )}
                        {filters.status && (
                            <Badge variant="secondary" className="gap-1">
                                Status: {filters.status}
                                <X
                                    className="h-3 w-3 cursor-pointer"
                                    onClick={() => updateFilter('status', undefined)}
                                />
                            </Badge>
                        )}
                        {filters.course && (
                            <Badge variant="secondary" className="gap-1">
                                Course: {filters.course}
                                <X
                                    className="h-3 w-3 cursor-pointer"
                                    onClick={() => updateFilter('course', undefined)}
                                />
                            </Badge>
                        )}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

