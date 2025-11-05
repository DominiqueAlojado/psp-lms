import HeadingSmall from '@/components/heading-small';
import { StatCard } from '@/components/stat-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { usePermissions } from '@/hooks/use-permissions';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { ClipboardList, Plus, Search } from 'lucide-react';
import { useCallback, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Assessments',
        href: '/assessments',
    },
];

interface Assessment {
    id: number;
    title: string;
    description: string | null;
    questions_count: number;
    total_points: number;
    passing_score: number;
    duration_minutes: number | null;
    is_published: boolean;
    is_available: boolean;
    available_from: string | null;
    available_until: string | null;
    created_by: string;
    created_at: string;
    updated_at: string;
}

interface PaginatedAssessments {
    data: Assessment[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface PageProps {
    assessments: PaginatedAssessments;
    filters: {
        search?: string;
        status?: string;
    };
}

export default function AssessmentsIndex() {
    const { assessments, filters } = usePage<PageProps>().props;
    const { hasPermission } = usePermissions();

    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');

    const handleSearch = useCallback(() => {
        router.get(
            '/assessments',
            {
                search: searchQuery || undefined,
                status: statusFilter || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    }, [searchQuery, statusFilter]);

    const handleClearFilters = () => {
        setSearchQuery('');
        setStatusFilter('');
        router.get('/assessments', {}, { preserveState: true, preserveScroll: true });
    };

    const hasActiveFilters = searchQuery || statusFilter;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Institution Assessments" />

            <div className="space-y-8 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="space-y-2">
                        <HeadingSmall icon={ClipboardList}>
                            Institution Assessments
                        </HeadingSmall>
                        <p className="text-sm text-muted-foreground">
                            Create and manage assessments for your institution
                        </p>
                    </div>
                    <TooltipProvider>
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <span className="inline-block">
                                    <Button
                                        onClick={() => {}}
                                        disabled={!hasPermission('create-assessments')}
                                    >
                                        <Plus className="mr-2 h-4 w-4" />
                                        Create Assessment
                                    </Button>
                                </span>
                            </TooltipTrigger>
                            {!hasPermission('create-assessments') && (
                                <TooltipContent>
                                    <p>
                                        You don't have permission to create
                                        assessments
                                    </p>
                                </TooltipContent>
                            )}
                        </Tooltip>
                    </TooltipProvider>
                </div>

                {/* Statistics */}
                <div className="grid gap-6 md:grid-cols-4">
                    <StatCard
                        title="Total Assessments"
                        value={assessments.total}
                        icon={ClipboardList}
                    />
                    <StatCard
                        title="Published"
                        value={
                            assessments.data.filter((a) => a.is_published).length
                        }
                        icon={ClipboardList}
                    />
                    <StatCard
                        title="Drafts"
                        value={
                            assessments.data.filter((a) => !a.is_published).length
                        }
                        icon={ClipboardList}
                    />
                    <StatCard
                        title="Available Now"
                        value={
                            assessments.data.filter((a) => a.is_available).length
                        }
                        icon={ClipboardList}
                    />
                </div>

                {/* Filters */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-1 gap-3">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                placeholder="Search assessments..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        handleSearch();
                                    }
                                }}
                                className="pl-9"
                            />
                        </div>
                        <select
                            value={statusFilter}
                            onChange={(e) => setStatusFilter(e.target.value)}
                            className="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option value="">All Status</option>
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                        </select>
                        <Button onClick={handleSearch}>Search</Button>
                        {hasActiveFilters && (
                            <Button variant="outline" onClick={handleClearFilters}>
                                Clear
                            </Button>
                        )}
                    </div>
                </div>

                {/* Assessments List */}
                <div className="rounded-lg border">
                    <div className="p-6">
                        {assessments.data.length === 0 ? (
                            <div className="py-12 text-center">
                                <ClipboardList className="mx-auto h-12 w-12 text-muted-foreground" />
                                <h3 className="mt-4 text-lg font-semibold">
                                    No assessments found
                                </h3>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    Get started by creating your first assessment
                                </p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {assessments.data.map((assessment) => (
                                    <div
                                        key={assessment.id}
                                        className="rounded-lg border p-4 hover:bg-muted/50"
                                    >
                                        <div className="flex items-start justify-between">
                                            <div className="flex-1">
                                                <h3 className="font-semibold">
                                                    {assessment.title}
                                                </h3>
                                                {assessment.description && (
                                                    <p className="mt-1 text-sm text-muted-foreground">
                                                        {assessment.description}
                                                    </p>
                                                )}
                                                <div className="mt-2 flex flex-wrap gap-4 text-sm text-muted-foreground">
                                                    <span>
                                                        {assessment.questions_count} questions
                                                    </span>
                                                    <span>
                                                        {assessment.total_points} points
                                                    </span>
                                                    {assessment.duration_minutes && (
                                                        <span>
                                                            {assessment.duration_minutes} min
                                                        </span>
                                                    )}
                                                    <span>
                                                        Pass: {assessment.passing_score}
                                                    </span>
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                {assessment.is_published ? (
                                                    <span className="rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700 dark:bg-green-900 dark:text-green-300">
                                                        Published
                                                    </span>
                                                ) : (
                                                    <span className="rounded-full bg-neutral-100 px-3 py-1 text-xs font-medium text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300">
                                                        Draft
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>

                {/* Pagination Info */}
                {assessments.total > 0 && (
                    <div className="text-sm text-muted-foreground">
                        Showing {(assessments.current_page - 1) * assessments.per_page + 1} to{' '}
                        {Math.min(assessments.current_page * assessments.per_page, assessments.total)} of{' '}
                        {assessments.total} assessments
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

