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
    const filterSelectClassName =
        'flex h-10 w-full rounded-xl border border-input/90 bg-background/90 px-3.5 py-2 text-sm shadow-[0_1px_2px_rgb(27_31_59_/_0.04)] ring-offset-background transition-[border-color,box-shadow] focus-visible:border-primary/30 focus-visible:ring-[3px] focus-visible:ring-ring/35 focus-visible:outline-none';

    return (
        <Card className="overflow-hidden border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))]">
            <CardContent className="space-y-6 p-6 md:p-7">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
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
                    <div className="flex flex-wrap items-center gap-3">
                        <Button
                            variant={showFilters ? 'default' : 'outline'}
                            onClick={() => setShowFilters(!showFilters)}
                        >
                            <Filter className="mr-2 h-4 w-4" />
                            Filters
                            {hasActiveFilters && (
                                <Badge variant="secondary" className="ml-1.5">
                                    {
                                        Object.keys(filters).filter(
                                            (k) =>
                                                filters[
                                                    k as keyof typeof filters
                                                ],
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
                </div>

                {/* Filter Options */}
                {showFilters && (
                    <div className="grid grid-cols-1 gap-4 border-t border-border/70 pt-5 md:grid-cols-2 xl:grid-cols-4">
                        {/* Organization Filter */}
                        <div className="space-y-2">
                            <label className="block text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                Organization
                            </label>
                            <select
                                className={filterSelectClassName}
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
                        <div className="space-y-2">
                            <label className="block text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                Year Level
                            </label>
                            <select
                                className={filterSelectClassName}
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
                        <div className="space-y-2">
                            <label className="block text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                Status
                            </label>
                            <select
                                className={filterSelectClassName}
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
                        <div className="space-y-2">
                            <label className="block text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                Course
                            </label>
                            <select
                                className={filterSelectClassName}
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
                    <div className="flex flex-wrap items-center gap-2 border-t border-border/70 pt-5">
                        {filters.search && (
                            <Badge variant="secondary" className="gap-1.5">
                                Search: {filters.search}
                                <X
                                    className="h-3 w-3 cursor-pointer opacity-60 transition-opacity hover:opacity-100"
                                    onClick={() => {
                                        setSearch('');
                                        updateFilter('search', undefined);
                                    }}
                                />
                            </Badge>
                        )}
                        {filters.organization_id && (
                            <Badge variant="secondary" className="gap-1.5">
                                Org:{' '}
                                {
                                    organizations.find(
                                        (o) =>
                                            o.id === Number(filters.organization_id),
                                    )?.name
                                }
                                <X
                                    className="h-3 w-3 cursor-pointer opacity-60 transition-opacity hover:opacity-100"
                                    onClick={() =>
                                        updateFilter('organization_id', undefined)
                                    }
                                />
                            </Badge>
                        )}
                        {filters.year_level && (
                            <Badge variant="secondary" className="gap-1.5">
                                Year: {filters.year_level}
                                <X
                                    className="h-3 w-3 cursor-pointer opacity-60 transition-opacity hover:opacity-100"
                                    onClick={() =>
                                        updateFilter('year_level', undefined)
                                    }
                                />
                            </Badge>
                        )}
                        {filters.status && (
                            <Badge variant="secondary" className="gap-1.5">
                                Status: {filters.status}
                                <X
                                    className="h-3 w-3 cursor-pointer opacity-60 transition-opacity hover:opacity-100"
                                    onClick={() => updateFilter('status', undefined)}
                                />
                            </Badge>
                        )}
                        {filters.course && (
                            <Badge variant="secondary" className="gap-1.5">
                                Course: {filters.course}
                                <X
                                    className="h-3 w-3 cursor-pointer opacity-60 transition-opacity hover:opacity-100"
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
