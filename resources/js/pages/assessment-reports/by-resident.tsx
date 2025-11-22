import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import AssessmentReportsLayout from '@/layouts/assessment-reports/assessment-reports-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { FileBarChart, Search, X } from 'lucide-react';
import { useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Assessment Reports',
        href: '/assessment-reports/by-resident',
    },
];

const YEAR_LEVELS = [
    'Pre-Resident',
    'First Year',
    'Second Year',
    'Third Year',
    'Fourth Year',
    'Graduate',
];

interface Attempt {
    id: number;
    resident_name: string;
    resident_email: string;
    year_level: string | null;
    exam_title: string;
    exam_category: string | null;
    score: number;
    total_points: number;
    percentage: number;
    passing_score: number;
    status: 'Passed' | 'Failed';
    organization_name: string;
    submitted_at: string;
    time_spent: string;
}

interface PaginatedAttempts {
    data: Attempt[];
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
}

interface Organization {
    id: number;
    name: string;
}

interface Exam {
    id: number | string;
    title: string;
    type?: string;
    original_id?: number;
}

interface PageProps {
    attempts: PaginatedAttempts;
    filters: {
        search?: string;
        exam?: number;
        organization?: number;
        year_level?: string;
        status?: string;
        date_from?: string;
        date_to?: string;
    };
    organizations: Organization[];
    exams: Exam[];
    isSystemAdmin: boolean;
    [key: string]: unknown;
}

export default function ByResidentReport() {
    const { attempts, filters, exams, isSystemAdmin } =
        usePage<PageProps>().props;
    const { auth } = usePage<SharedData>().props;
    const currentOrganization = auth.currentOrganization;

    const [search, setSearch] = useState(filters.search || '');
    const [examFilter, setExamFilter] = useState(
        filters.exam?.toString() || 'all',
    );
    const [yearLevelFilter, setYearLevelFilter] = useState(
        filters.year_level || '',
    );
    const [statusFilter, setStatusFilter] = useState(filters.status || '');
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');

    // Filter exams based on current organization type
    const filteredExams = useMemo(() => {
        if (!currentOrganization) {
            return exams;
        }

        const orgType = currentOrganization.type?.toLowerCase();

        // If organization is national or inservice, show only inservice exams
        if (orgType === 'national' || orgType === 'inservice') {
            return exams.filter((exam) => exam.type === 'inservice');
        }

        // If organization is institution, show only institution exams
        if (orgType === 'institution') {
            return exams.filter((exam) => exam.type === 'institution');
        }

        // Default: show all exams
        return exams;
    }, [exams, currentOrganization]);

    const handleSearch = () => {
        router.get(
            '/assessment-reports/by-resident',
            {
                search: search || undefined,
                exam:
                    examFilter && examFilter !== '' && examFilter !== 'all'
                        ? examFilter
                        : undefined,
                year_level: yearLevelFilter || undefined,
                status: statusFilter || undefined,
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const clearFilters = () => {
        setSearch('');
        setExamFilter('all');
        setYearLevelFilter('');
        setStatusFilter('');
        setDateFrom('');
        setDateTo('');
        router.get(
            '/assessment-reports/by-resident',
            {},
            { preserveState: true },
        );
    };

    const hasActiveFilters =
        filters.search ||
        filters.exam ||
        filters.year_level ||
        filters.status ||
        filters.date_from ||
        filters.date_to;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Assessment Reports - By Resident" />

            <AssessmentReportsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Exam Results by Resident"
                        description="View and filter resident exam performance"
                    />

                    {/* Filters */}
                    <Card>
                        <CardContent className="p-4">
                            <div className="space-y-4">
                                {/* Search */}
                                <div className="space-y-2">
                                    <Label>Search Resident</Label>
                                    <div className="relative">
                                        <Search className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                        <Input
                                            placeholder="Search by name or email..."
                                            value={search}
                                            onChange={(e) =>
                                                setSearch(e.target.value)
                                            }
                                            onKeyDown={(e) => {
                                                if (e.key === 'Enter') {
                                                    handleSearch();
                                                }
                                            }}
                                            className="pl-9"
                                        />
                                    </div>
                                </div>

                                {/* Exam */}
                                <div className="space-y-2">
                                    <Label>Exam</Label>
                                    <Select
                                        value={examFilter}
                                        onValueChange={(value) => {
                                            setExamFilter(value);
                                        }}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="All Exams" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">
                                                All Exams
                                            </SelectItem>
                                            {filteredExams.map((exam) => (
                                                <SelectItem
                                                    key={exam.id}
                                                    value={String(exam.id)}
                                                >
                                                    {exam.title}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                {/* Year Level & Status */}
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label>Year Level</Label>
                                        <Select
                                            value={yearLevelFilter}
                                            onValueChange={setYearLevelFilter}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="All Year Levels" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {YEAR_LEVELS.map((level) => (
                                                    <SelectItem
                                                        key={level}
                                                        value={level}
                                                    >
                                                        {level}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="space-y-2">
                                        <Label>Status</Label>
                                        <Select
                                            value={statusFilter}
                                            onValueChange={setStatusFilter}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="All Status" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="passed">
                                                    Passed
                                                </SelectItem>
                                                <SelectItem value="failed">
                                                    Failed
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                {/* Date Range */}
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label>Date From</Label>
                                        <Input
                                            type="date"
                                            value={dateFrom}
                                            onChange={(e) =>
                                                setDateFrom(e.target.value)
                                            }
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Date To</Label>
                                        <Input
                                            type="date"
                                            value={dateTo}
                                            onChange={(e) =>
                                                setDateTo(e.target.value)
                                            }
                                        />
                                    </div>
                                </div>

                                {/* Action Buttons */}
                                <div className="flex gap-2">
                                    <Button onClick={handleSearch}>
                                        <Search className="mr-2 h-4 w-4" />
                                        Search
                                    </Button>
                                    {hasActiveFilters && (
                                        <Button
                                            variant="outline"
                                            onClick={clearFilters}
                                        >
                                            <X className="mr-2 h-4 w-4" />
                                            Clear Filters
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Results */}
                    {attempts.data.length === 0 ? (
                        <Card>
                            <CardContent className="p-12 text-center">
                                <FileBarChart className="mx-auto h-12 w-12 text-muted-foreground" />
                                <p className="mt-4 text-sm text-muted-foreground">
                                    {hasActiveFilters
                                        ? 'No exam attempts found matching your filters'
                                        : 'No completed exam attempts yet'}
                                </p>
                            </CardContent>
                        </Card>
                    ) : (
                        <Card>
                            <CardContent className="p-0">
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Resident</TableHead>
                                                {isSystemAdmin && (
                                                    <TableHead>
                                                        Institution
                                                    </TableHead>
                                                )}
                                                <TableHead>Exam</TableHead>
                                                <TableHead className="text-right">
                                                    Score
                                                </TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead>Submitted</TableHead>
                                                <TableHead>Duration</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {attempts.data.map((attempt) => (
                                                <TableRow key={attempt.id}>
                                                    <TableCell>
                                                        <div>
                                                            <div className="font-medium">
                                                                {
                                                                    attempt.resident_name
                                                                }
                                                            </div>
                                                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                                                <span>
                                                                    {
                                                                        attempt.resident_email
                                                                    }
                                                                </span>
                                                                {attempt.year_level && (
                                                                    <>
                                                                        <span>
                                                                            •
                                                                        </span>
                                                                        <Badge
                                                                            variant="secondary"
                                                                            className="text-xs"
                                                                        >
                                                                            {
                                                                                attempt.year_level
                                                                            }
                                                                        </Badge>
                                                                    </>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                    {isSystemAdmin && (
                                                        <TableCell className="text-sm">
                                                            {
                                                                attempt.organization_name
                                                            }
                                                        </TableCell>
                                                    )}
                                                    <TableCell>
                                                        <div>
                                                            <div className="font-medium">
                                                                {
                                                                    attempt.exam_title
                                                                }
                                                            </div>
                                                            {attempt.exam_category && (
                                                                <div className="text-xs text-muted-foreground">
                                                                    {
                                                                        attempt.exam_category
                                                                    }
                                                                </div>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        <div className="font-mono">
                                                            {attempt.score}/
                                                            {
                                                                attempt.total_points
                                                            }
                                                        </div>
                                                        <div className="text-xs text-muted-foreground">
                                                            {attempt.percentage.toFixed(
                                                                1,
                                                            )}
                                                            %
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge
                                                            variant={
                                                                attempt.status ===
                                                                'Passed'
                                                                    ? 'default'
                                                                    : 'destructive'
                                                            }
                                                        >
                                                            {attempt.status}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="text-sm">
                                                        {attempt.submitted_at}
                                                    </TableCell>
                                                    <TableCell className="text-sm text-muted-foreground">
                                                        {attempt.time_spent}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Pagination */}
                    {attempts.data.length > 0 && (
                        <div className="space-y-4">
                            {/* Pagination Info */}
                            <div className="text-center text-sm text-muted-foreground">
                                Showing{' '}
                                {(attempts.current_page - 1) *
                                    attempts.per_page +
                                    1}{' '}
                                to{' '}
                                {Math.min(
                                    attempts.current_page * attempts.per_page,
                                    attempts.total,
                                )}{' '}
                                of {attempts.total} results
                            </div>

                            {/* Pagination Controls */}
                            {attempts.last_page > 1 && (
                                <div className="flex items-center justify-center gap-2">
                                    {/* Previous Button */}
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={attempts.current_page === 1}
                                        onClick={() => {
                                            router.get(
                                                '/assessment-reports/by-resident',
                                                {
                                                    ...filters,
                                                    page:
                                                        attempts.current_page -
                                                        1,
                                                },
                                                {
                                                    preserveState: true,
                                                    preserveScroll: true,
                                                },
                                            );
                                        }}
                                    >
                                        Previous
                                    </Button>

                                    {/* Page Numbers */}
                                    {Array.from(
                                        { length: attempts.last_page },
                                        (_, i) => i + 1,
                                    )
                                        .filter((page) => {
                                            // Show first page, last page, current page, and pages around current
                                            if (page === 1) {
                                                return true;
                                            }
                                            if (page === attempts.last_page) {
                                                return true;
                                            }
                                            if (
                                                page >=
                                                    attempts.current_page - 1 &&
                                                page <=
                                                    attempts.current_page + 1
                                            ) {
                                                return true;
                                            }
                                            return false;
                                        })
                                        .map((page, index, array) => {
                                            // Add ellipsis if there's a gap
                                            const showEllipsisBefore =
                                                index > 0 &&
                                                array[index - 1] < page - 1;
                                            return (
                                                <div
                                                    key={page}
                                                    className="flex items-center gap-2"
                                                >
                                                    {showEllipsisBefore && (
                                                        <span className="px-2 text-muted-foreground">
                                                            ...
                                                        </span>
                                                    )}
                                                    <Button
                                                        variant={
                                                            page ===
                                                            attempts.current_page
                                                                ? 'default'
                                                                : 'outline'
                                                        }
                                                        size="sm"
                                                        onClick={() => {
                                                            router.get(
                                                                '/assessment-reports/by-resident',
                                                                {
                                                                    ...filters,
                                                                    page,
                                                                },
                                                                {
                                                                    preserveState: true,
                                                                    preserveScroll: true,
                                                                },
                                                            );
                                                        }}
                                                    >
                                                        {page}
                                                    </Button>
                                                </div>
                                            );
                                        })}

                                    {/* Next Button */}
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={
                                            attempts.current_page ===
                                            attempts.last_page
                                        }
                                        onClick={() => {
                                            router.get(
                                                '/assessment-reports/by-resident',
                                                {
                                                    ...filters,
                                                    page:
                                                        attempts.current_page +
                                                        1,
                                                },
                                                {
                                                    preserveState: true,
                                                    preserveScroll: true,
                                                },
                                            );
                                        }}
                                    >
                                        Next
                                    </Button>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </AssessmentReportsLayout>
        </AppLayout>
    );
}
