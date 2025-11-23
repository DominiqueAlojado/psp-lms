import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, FileText } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'CME/CPD Credits',
        href: '/cme-credits',
    },
    {
        title: 'History',
        href: '/cme-credits/history',
    },
];

interface Credit {
    id: number;
    credits: number;
    description: string;
    source_type: string;
    earned_at: string;
    status: string;
    source: string | null;
}

interface PaginatedCredits {
    data: Credit[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface PageProps {
    credits: PaginatedCredits;
}

export default function CmeCreditsHistory() {
    const { credits } = usePage<PageProps>().props;

    const getSourceTypeLabel = (sourceType: string): string => {
        return {
            event: 'Event',
            exam_institution: 'Institution Exam',
            exam_national: 'In-Service Exam',
        }[sourceType] || sourceType;
    };

    const getSourceTypeColor = (sourceType: string): string => {
        return {
            event: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            exam_institution: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
            exam_national: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        }[sourceType] || 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400';
    };

    const getStatusColor = (status: string): string => {
        return {
            approved: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
            pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
            revoked: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        }[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400';
    };

    const handlePageChange = (page: number) => {
        router.get('/cme-credits/history', { page }, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="CME/CPD Credits History" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex items-start justify-between gap-4">
                    <div className="flex items-center gap-4">
                        <Button
                            variant="ghost"
                            size="icon"
                            asChild
                        >
                            <Link href="/cme-credits">
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <HeadingSmall
                            title="Credit History"
                            description="Complete history of all CME/CPD credits earned"
                        />
                    </div>
                </div>

                {credits.data.length === 0 ? (
                    <Card>
                        <CardContent className="p-8 text-center">
                            <FileText className="mx-auto h-12 w-12 text-muted-foreground" />
                            <p className="mt-4 text-sm text-muted-foreground">
                                No credits earned yet
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <>
                        <Card>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Date</TableHead>
                                            <TableHead>Source</TableHead>
                                            <TableHead>Description</TableHead>
                                            <TableHead className="text-right">
                                                Credits
                                            </TableHead>
                                            <TableHead>Status</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {credits.data.map((credit) => (
                                            <TableRow key={credit.id}>
                                                <TableCell>
                                                    {credit.earned_at}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        className={getSourceTypeColor(
                                                            credit.source_type,
                                                        )}
                                                    >
                                                        {getSourceTypeLabel(
                                                            credit.source_type,
                                                        )}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <div>
                                                        <p className="text-sm">
                                                            {credit.description}
                                                        </p>
                                                        {credit.source && (
                                                            <p className="text-xs text-muted-foreground">
                                                                {credit.source}
                                                            </p>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-right font-semibold">
                                                    {credit.credits.toFixed(2)}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        className={getStatusColor(
                                                            credit.status,
                                                        )}
                                                    >
                                                        {credit.status.charAt(0).toUpperCase() +
                                                            credit.status.slice(1)}
                                                    </Badge>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>

                        {/* Pagination */}
                        {credits.last_page > 1 && (
                            <div className="flex items-center justify-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={credits.current_page === 1}
                                    onClick={() =>
                                        handlePageChange(
                                            credits.current_page - 1,
                                        )
                                    }
                                >
                                    Previous
                                </Button>
                                <span className="text-sm text-muted-foreground">
                                    Page {credits.current_page} of{' '}
                                    {credits.last_page}
                                </span>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={
                                        credits.current_page ===
                                        credits.last_page
                                    }
                                    onClick={() =>
                                        handlePageChange(
                                            credits.current_page + 1,
                                        )
                                    }
                                >
                                    Next
                                </Button>
                            </div>
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}

