import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Award, Calendar, FileText, History, TrendingUp } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'CME/CPD Credits',
        href: '/cme-credits',
    },
];

interface PageProps {
    totalCredits: number;
    totalCmeCredits: number;
    totalCpdCredits: number;
    currentYearCredits: number;
    currentYearCmeCredits: number;
    currentYearCpdCredits: number;
    creditsBySource: {
        event?: number;
        exam_institution?: number;
        exam_national?: number;
        assignment_submission?: number;
    };
    creditsByType: {
        cme?: number;
        cpd?: number;
    };
    recentCredits: Array<{
        id: number;
        credits: number;
        description: string;
        source_type: string;
        credit_type: string;
        earned_at: string;
        source: string | null;
    }>;
}

export default function CmeCreditsIndex() {
    const {
        totalCredits,
        totalCmeCredits,
        totalCpdCredits,
        currentYearCredits,
        currentYearCmeCredits,
        currentYearCpdCredits,
        creditsBySource,
        creditsByType,
        recentCredits,
    } = usePage<PageProps>().props;

    const getSourceTypeLabel = (sourceType: string): string => {
        return {
            event: 'Event',
            exam_institution: 'Institution Exam',
            exam_national: 'In-Service Exam',
            assignment_submission: 'Assignment',
        }[sourceType] || sourceType;
    };

    const getSourceTypeColor = (sourceType: string): string => {
        return {
            event: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            exam_institution: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
            exam_national: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
            assignment_submission: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
        }[sourceType] || 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400';
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="CME/CPD Credits" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex items-start justify-between gap-4">
                    <HeadingSmall
                        title="CME/CPD Credits"
                        description="Track your Continuing Medical Education and Continuing Professional Development credits"
                    />
                    <Button asChild variant="outline">
                        <Link href="/cme-credits/history">
                            <History className="mr-2 h-4 w-4" />
                            View History
                        </Link>
                    </Button>
                </div>

                {/* Summary Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {/* Total Credits */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Total Credits
                            </CardTitle>
                            <Award className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {totalCredits.toFixed(2)}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                All time (CME + CPD)
                            </p>
                        </CardContent>
                    </Card>

                    {/* CME Credits */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                CME Credits
                            </CardTitle>
                            <Award className="h-4 w-4 text-blue-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">
                                {totalCmeCredits.toFixed(2)}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Continuing Medical Education
                            </p>
                        </CardContent>
                    </Card>

                    {/* CPD Credits */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                CPD Credits
                            </CardTitle>
                            <Award className="h-4 w-4 text-purple-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-purple-600">
                                {totalCpdCredits.toFixed(2)}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Continuing Professional Development
                            </p>
                        </CardContent>
                    </Card>

                    {/* Current Year Credits */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                This Year
                            </CardTitle>
                            <Calendar className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {currentYearCredits.toFixed(2)}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                CME: {currentYearCmeCredits.toFixed(2)} | CPD: {currentYearCpdCredits.toFixed(2)}
                            </p>
                        </CardContent>
                    </Card>

                </div>

                {/* Credits Breakdown */}
                <div className="grid gap-4 md:grid-cols-2">
                    {/* Credits by Source */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                By Source
                            </CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                {Object.entries(creditsBySource).map(
                                    ([source, credits]) => (
                                        <div
                                            key={source}
                                            className="flex items-center justify-between"
                                        >
                                            <span className="text-sm text-muted-foreground">
                                                {getSourceTypeLabel(source)}
                                            </span>
                                            <span className="text-sm font-medium">
                                                {credits.toFixed(2)}
                                            </span>
                                        </div>
                                    ),
                                )}
                                {Object.keys(creditsBySource).length === 0 && (
                                    <p className="text-xs text-muted-foreground">
                                        No credits yet
                                    </p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Credits by Type */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                By Type
                            </CardTitle>
                            <Award className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                {creditsByType.cme !== undefined && (
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm text-muted-foreground">
                                            CME
                                        </span>
                                        <span className="text-sm font-medium text-blue-600">
                                            {creditsByType.cme.toFixed(2)}
                                        </span>
                                    </div>
                                )}
                                {creditsByType.cpd !== undefined && (
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm text-muted-foreground">
                                            CPD
                                        </span>
                                        <span className="text-sm font-medium text-purple-600">
                                            {creditsByType.cpd.toFixed(2)}
                                        </span>
                                    </div>
                                )}
                                {Object.keys(creditsByType).length === 0 && (
                                    <p className="text-xs text-muted-foreground">
                                        No credits yet
                                    </p>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Recent Credits */}
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Credits</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {recentCredits.length === 0 ? (
                            <div className="py-8 text-center">
                                <FileText className="mx-auto h-12 w-12 text-muted-foreground" />
                                <p className="mt-4 text-sm text-muted-foreground">
                                    No credits earned yet
                                </p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {recentCredits.map((credit) => (
                                    <div
                                        key={credit.id}
                                        className="flex items-start justify-between gap-4 rounded-lg border p-4"
                                    >
                                        <div className="flex-1">
                                            <div className="flex items-center gap-2">
                                                <Badge
                                                    className={getSourceTypeColor(
                                                        credit.source_type,
                                                    )}
                                                >
                                                    {getSourceTypeLabel(
                                                        credit.source_type,
                                                    )}
                                                </Badge>
                                                <Badge
                                                    variant={
                                                        credit.credit_type === 'cpd'
                                                            ? 'secondary'
                                                            : 'default'
                                                    }
                                                    className={
                                                        credit.credit_type === 'cpd'
                                                            ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'
                                                            : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                                                    }
                                                >
                                                    {credit.credit_type === 'cpd'
                                                        ? 'CPD'
                                                        : 'CME'}
                                                </Badge>
                                                <span className="text-sm font-semibold">
                                                    {credit.credits.toFixed(2)}{' '}
                                                    Credits
                                                </span>
                                            </div>
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {credit.description}
                                            </p>
                                            {credit.source && (
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    {credit.source}
                                                </p>
                                            )}
                                        </div>
                                        <div className="text-right">
                                            <p className="text-xs text-muted-foreground">
                                                {credit.earned_at}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

