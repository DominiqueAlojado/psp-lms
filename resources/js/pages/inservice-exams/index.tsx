import HeadingSmall from '@/components/heading-small';
import { StatCard } from '@/components/stat-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { GraduationCap, Plus, Search } from 'lucide-react';
import { useCallback, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'In-Service Exams',
        href: '/inservice-exams/active',
    },
];

interface NationalAssessment {
    id: number;
    title: string;
    description: string | null;
    exam_year: number;
    exam_period: string;
    questions_count: number;
    total_points: number;
    passing_score: number;
    duration_minutes: number | null;
    is_published: boolean;
    is_available: boolean;
    scheduled_date: string | null;
    results_release_date: string | null;
    can_view_results: boolean;
    national_ranking_enabled: boolean;
    created_by: string;
    created_at: string;
    updated_at: string;
}

interface PaginatedAssessments {
    data: NationalAssessment[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface PageProps {
    assessments: PaginatedAssessments;
    filters: {
        search?: string;
        year?: string;
        status?: string;
    };
    years: number[];
}

export default function InServiceIndex() {
    const { assessments, filters, years } = usePage<PageProps>().props;
    const { auth } = usePage<{ auth: { user: any } }>().props;
    const canManage = auth.user?.roles?.some((role: any) =>
        ['System Admin', 'BOP'].includes(role),
    );

    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [yearFilter, setYearFilter] = useState(filters.year || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');

    const handleSearch = useCallback(() => {
        router.get(
            '/in-service',
            {
                search: searchQuery || undefined,
                year: yearFilter || undefined,
                status: statusFilter || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    }, [searchQuery, yearFilter, statusFilter]);

    const handleClearFilters = () => {
        setSearchQuery('');
        setYearFilter('');
        setStatusFilter('');
        router.get(
            '/in-service',
            {},
            { preserveState: true, preserveScroll: true },
        );
    };

    const hasActiveFilters = searchQuery || yearFilter || statusFilter;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="National In-Service Exams" />

            <div className="space-y-8 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="space-y-2">
                        <HeadingSmall icon={GraduationCap}>
                            National In-Service Exams
                        </HeadingSmall>
                        <p className="text-sm text-muted-foreground">
                            Standardized national examinations for all pathology
                            residents
                        </p>
                    </div>
                    {canManage && (
                        <Button onClick={() => router.visit('/inservice-exams/create')}>
                            <Plus className="mr-2 h-4 w-4" />
                            Create In-Service Exam
                        </Button>
                    )}
                </div>

                {/* Statistics */}
                <div className="grid gap-6 md:grid-cols-4">
                    <StatCard
                        title="Total Exams"
                        value={assessments.total}
                        icon={GraduationCap}
                    />
                    <StatCard
                        title="Published"
                        value={
                            assessments.data.filter((a) => a.is_published)
                                .length
                        }
                        icon={GraduationCap}
                    />
                    <StatCard
                        title="Available Now"
                        value={
                            assessments.data.filter((a) => a.is_available)
                                .length
                        }
                        icon={GraduationCap}
                    />
                    <StatCard
                        title="With Rankings"
                        value={
                            assessments.data.filter(
                                (a) => a.national_ranking_enabled,
                            ).length
                        }
                        icon={GraduationCap}
                    />
                </div>

                {/* Filters */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-1 gap-3">
                        <div className="relative flex-1">
                            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                placeholder="Search exams..."
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
                            value={yearFilter}
                            onChange={(e) => setYearFilter(e.target.value)}
                            className="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option value="">All Years</option>
                            {years.map((year) => (
                                <option key={year} value={year}>
                                    {year}
                                </option>
                            ))}
                        </select>
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
                            <Button
                                variant="outline"
                                onClick={handleClearFilters}
                            >
                                Clear
                            </Button>
                        )}
                    </div>
                </div>

                {/* Exams List */}
                <div className="rounded-lg border">
                    <div className="p-6">
                        {assessments.data.length === 0 ? (
                            <div className="py-12 text-center">
                                <GraduationCap className="mx-auto h-12 w-12 text-muted-foreground" />
                                <h3 className="mt-4 text-lg font-semibold">
                                    No in-service exams found
                                </h3>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    {canManage
                                        ? 'Create the first national in-service exam'
                                        : 'Check back later for upcoming exams'}
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
                                                <div className="flex items-center gap-2">
                                                    <h3 className="font-semibold">
                                                        {assessment.title}
                                                    </h3>
                                                    <span className="rounded bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">
                                                        {assessment.exam_year} -{' '}
                                                        {assessment.exam_period}
                                                    </span>
                                                </div>
                                                {assessment.description && (
                                                    <p className="mt-1 text-sm text-muted-foreground">
                                                        {assessment.description}
                                                    </p>
                                                )}
                                                <div className="mt-2 flex flex-wrap gap-4 text-sm text-muted-foreground">
                                                    <span>
                                                        {
                                                            assessment.questions_count
                                                        }{' '}
                                                        questions
                                                    </span>
                                                    <span>
                                                        {
                                                            assessment.total_points
                                                        }{' '}
                                                        points
                                                    </span>
                                                    {assessment.duration_minutes && (
                                                        <span>
                                                            {
                                                                assessment.duration_minutes
                                                            }{' '}
                                                            min
                                                        </span>
                                                    )}
                                                    <span>
                                                        Pass:{' '}
                                                        {
                                                            assessment.passing_score
                                                        }
                                                    </span>
                                                    {assessment.scheduled_date && (
                                                        <span>
                                                            Scheduled:{' '}
                                                            {
                                                                assessment.scheduled_date
                                                            }
                                                        </span>
                                                    )}
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
                                                {assessment.national_ranking_enabled && (
                                                    <span className="rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                                                        Ranked
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
                        Showing{' '}
                        {(assessments.current_page - 1) * assessments.per_page +
                            1}{' '}
                        to{' '}
                        {Math.min(
                            assessments.current_page * assessments.per_page,
                            assessments.total,
                        )}{' '}
                        of {assessments.total} exams
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
