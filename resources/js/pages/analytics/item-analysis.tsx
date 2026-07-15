import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import AnalyticsLayout from '@/layouts/analytics/analytics-layout';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import {
    BarChart3,
    CheckCircle2,
    ChevronDown,
    ChevronUp,
    HelpCircle,
    X,
} from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Analytics',
        href: '/analytics/item-analysis',
    },
];

interface Exam {
    id: string;
    title: string;
    category: string | null;
    type: 'institution' | 'national';
}

interface Distractor {
    choice_id: number;
    choice_text: string;
    is_correct: boolean;
    count: number;
    percentage: number;
}

interface Item {
    question_id: number;
    question_text: string;
    question_type: string;
    topic: string;
    points: number;
    order: number;
    difficulty_index: number;
    difficulty_label: string;
    discrimination_index: number;
    discrimination_label: string;
    point_biserial: number;
    correct_count: number;
    incorrect_count: number;
    total_responses: number;
    quality: string;
    distractor_analysis: Distractor[];
}

interface ItemAnalysis {
    exam: {
        id: number;
        title: string;
        category: string | null;
    };
    total_attempts: number;
    items: Item[];
}

interface PageProps {
    exams: Exam[];
    itemAnalysis: ItemAnalysis | null;
    filters: {
        exam?: string;
        date_from?: string;
        date_to?: string;
    };
    [key: string]: unknown;
}

export default function ItemAnalysis() {
    const { exams, itemAnalysis, filters } = usePage<PageProps>().props;

    const [examFilter, setExamFilter] = useState(
        filters.exam?.toString() || '',
    );
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');
    const [expandedItems, setExpandedItems] = useState<Set<number>>(new Set());

    const handleSearch = () => {
        router.get(
            '/analytics/item-analysis',
            {
                exam: examFilter || undefined,
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const clearFilters = () => {
        setExamFilter('');
        setDateFrom('');
        setDateTo('');
        router.get('/analytics/item-analysis', {}, { preserveState: true });
    };

    const hasActiveFilters =
        filters.exam || filters.date_from || filters.date_to;

    const toggleItem = (questionId: number) => {
        const newExpanded = new Set(expandedItems);
        if (newExpanded.has(questionId)) {
            newExpanded.delete(questionId);
        } else {
            newExpanded.add(questionId);
        }
        setExpandedItems(newExpanded);
    };

    const getQualityBadgeVariant = (quality: string) => {
        switch (quality) {
            case 'Good':
                return 'default';
            case 'Acceptable':
                return 'secondary';
            case 'Marginal':
                return 'outline';
            case 'Needs Review':
                return 'destructive';
            default:
                return 'outline';
        }
    };

    const getDifficultyBadgeVariant = (label: string) => {
        if (label.includes('Very Easy') || label.includes('Very Difficult')) {
            return 'destructive';
        }
        if (label.includes('Easy') || label.includes('Difficult')) {
            return 'secondary';
        }
        return 'default';
    };

    const getDiscriminationBadgeVariant = (label: string) => {
        if (label.includes('Excellent') || label.includes('Good')) {
            return 'default';
        }
        if (label.includes('Fair')) {
            return 'secondary';
        }
        return 'destructive';
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Item Analysis" />

            <AnalyticsLayout>
                <div className="space-y-6">
                    {/* Header */}
                    <div className="flex items-start justify-between gap-4">
                        <HeadingSmall
                            title="Item Analysis"
                            description="Detailed analysis of individual test items including difficulty, discrimination, and distractor effectiveness"
                        />
                    </div>

                    {/* Filters */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Filters</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div className="space-y-2">
                                    <Label htmlFor="exam">Select Exam</Label>
                                    <Select
                                        value={examFilter}
                                        onValueChange={setExamFilter}
                                    >
                                        <SelectTrigger id="exam">
                                            <SelectValue placeholder="Select an exam" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {exams.map((exam) => (
                                                <SelectItem
                                                    key={exam.id}
                                                    value={exam.id}
                                                >
                                                    {exam.title}
                                                    {exam.type ===
                                                        'national' && (
                                                        <Badge
                                                            variant="secondary"
                                                            className="ml-2"
                                                        >
                                                            National
                                                        </Badge>
                                                    )}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="date_from">Date From</Label>
                                    <Input
                                        id="date_from"
                                        type="date"
                                        value={dateFrom}
                                        onChange={(e) =>
                                            setDateFrom(e.target.value)
                                        }
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="date_to">Date To</Label>
                                    <Input
                                        id="date_to"
                                        type="date"
                                        value={dateTo}
                                        onChange={(e) =>
                                            setDateTo(e.target.value)
                                        }
                                    />
                                </div>
                            </div>

                            <div className="mt-4 flex items-center gap-2">
                                <Button onClick={handleSearch}>
                                    <BarChart3 className="mr-2 h-4 w-4" />
                                    Analyze
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
                        </CardContent>
                    </Card>

                    {/* Item Analysis Results */}
                    {itemAnalysis && (
                        <div className="space-y-6">
                            {/* Summary Card */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>
                                        {itemAnalysis.exam.title}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                                        <div>
                                            <p className="text-sm text-muted-foreground">
                                                Total Attempts
                                            </p>
                                            <p className="text-2xl font-bold">
                                                {itemAnalysis.total_attempts}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground">
                                                Total Items
                                            </p>
                                            <p className="text-2xl font-bold">
                                                {itemAnalysis.items.length}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground">
                                                Items Needing Review
                                            </p>
                                            <p className="text-2xl font-bold text-destructive">
                                                {
                                                    itemAnalysis.items.filter(
                                                        (item) =>
                                                            item.quality ===
                                                            'Needs Review',
                                                    ).length
                                                }
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Items Table */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Item Analysis Results</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="overflow-x-auto">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead className="w-12"></TableHead>
                                                    <TableHead>
                                                        Item #
                                                    </TableHead>
                                                    <TableHead>
                                                        Question
                                                    </TableHead>
                                                    <TableHead>Topic</TableHead>
                                                    <TableHead>
                                                        Difficulty
                                                    </TableHead>
                                                    <TableHead>
                                                        Discrimination
                                                    </TableHead>
                                                    <TableHead>
                                                        Point Biserial
                                                    </TableHead>
                                                    <TableHead>
                                                        Quality
                                                    </TableHead>
                                                    <TableHead>
                                                        Correct / Total
                                                    </TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {itemAnalysis.items.map(
                                                    (item) => (
                                                        <>
                                                            <TableRow
                                                                key={
                                                                    item.question_id
                                                                }
                                                            >
                                                                <TableCell>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        onClick={() =>
                                                                            toggleItem(
                                                                                item.question_id,
                                                                            )
                                                                        }
                                                                    >
                                                                        {expandedItems.has(
                                                                            item.question_id,
                                                                        ) ? (
                                                                            <ChevronUp className="h-4 w-4" />
                                                                        ) : (
                                                                            <ChevronDown className="h-4 w-4" />
                                                                        )}
                                                                    </Button>
                                                                </TableCell>
                                                                <TableCell>
                                                                    {item.order}
                                                                </TableCell>
                                                                <TableCell className="max-w-md truncate">
                                                                    {
                                                                        item.question_text
                                                                    }
                                                                </TableCell>
                                                                <TableCell>
                                                                    {item.topic}
                                                                </TableCell>
                                                                <TableCell>
                                                                    <div className="flex flex-col gap-1">
                                                                        <Badge
                                                                            variant={getDifficultyBadgeVariant(
                                                                                item.difficulty_label,
                                                                            )}
                                                                        >
                                                                            {
                                                                                item.difficulty_index
                                                                            }
                                                                        </Badge>
                                                                        <span className="text-xs text-muted-foreground">
                                                                            {
                                                                                item.difficulty_label
                                                                            }
                                                                        </span>
                                                                    </div>
                                                                </TableCell>
                                                                <TableCell>
                                                                    <div className="flex flex-col gap-1">
                                                                        <Badge
                                                                            variant={getDiscriminationBadgeVariant(
                                                                                item.discrimination_label,
                                                                            )}
                                                                        >
                                                                            {
                                                                                item.discrimination_index
                                                                            }
                                                                        </Badge>
                                                                        <span className="text-xs text-muted-foreground">
                                                                            {
                                                                                item.discrimination_label
                                                                            }
                                                                        </span>
                                                                    </div>
                                                                </TableCell>
                                                                <TableCell>
                                                                    {item.point_biserial.toFixed(
                                                                        3,
                                                                    )}
                                                                </TableCell>
                                                                <TableCell>
                                                                    <Badge
                                                                        variant={getQualityBadgeVariant(
                                                                            item.quality,
                                                                        )}
                                                                    >
                                                                        {
                                                                            item.quality
                                                                        }
                                                                    </Badge>
                                                                </TableCell>
                                                                <TableCell>
                                                                    {
                                                                        item.correct_count
                                                                    }{' '}
                                                                    /{' '}
                                                                    {
                                                                        item.total_responses
                                                                    }
                                                                </TableCell>
                                                            </TableRow>
                                                            {expandedItems.has(
                                                                item.question_id,
                                                            ) && (
                                                                <TableRow>
                                                                    <TableCell
                                                                        colSpan={
                                                                            9
                                                                        }
                                                                        className="bg-muted/50"
                                                                    >
                                                                        <div className="max-w-4xl space-y-4 p-4">
                                                                            <div>
                                                                                <h4 className="mb-2 font-semibold">
                                                                                    Full
                                                                                    Question
                                                                                </h4>
                                                                                <p className="text-sm">
                                                                                    {
                                                                                        item.question_text
                                                                                    }
                                                                                </p>
                                                                            </div>
                                                                            <div>
                                                                                <h4 className="mb-2 font-semibold">
                                                                                    Distractor
                                                                                    Analysis
                                                                                </h4>
                                                                                <div className="space-y-2">
                                                                                    {item.distractor_analysis.map(
                                                                                        (
                                                                                            distractor,
                                                                                        ) => (
                                                                                            <div
                                                                                                key={
                                                                                                    distractor.choice_id
                                                                                                }
                                                                                                className="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-4 rounded border bg-background px-3 py-2"
                                                                                            >
                                                                                                <div className="flex items-center gap-2">
                                                                                                    {distractor.is_correct ? (
                                                                                                        <CheckCircle2 className="h-4 w-4 text-green-600" />
                                                                                                    ) : (
                                                                                                        <HelpCircle className="h-4 w-4 text-muted-foreground" />
                                                                                                    )}
                                                                                                    <span
                                                                                                        className={
                                                                                                            distractor.is_correct
                                                                                                                ? 'min-w-0 font-semibold'
                                                                                                                : 'min-w-0'
                                                                                                        }
                                                                                                    >
                                                                                                        {
                                                                                                            distractor.choice_text
                                                                                                        }
                                                                                                    </span>
                                                                                                </div>
                                                                                                <div className="shrink-0 text-right">
                                                                                                    <span className="tabular-nums text-sm text-muted-foreground">
                                                                                                        {
                                                                                                            distractor.count
                                                                                                        }{' '}
                                                                                                        (
                                                                                                        {
                                                                                                            distractor.percentage
                                                                                                        }
                                                                                                        %)
                                                                                                    </span>
                                                                                                </div>
                                                                                            </div>
                                                                                        ),
                                                                                    )}
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </TableCell>
                                                                </TableRow>
                                                            )}
                                                        </>
                                                    ),
                                                )}
                                            </TableBody>
                                        </Table>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    )}

                    {/* No Results Message */}
                    {!itemAnalysis && examFilter && (
                        <Card>
                            <CardContent className="py-8 text-center">
                                <p className="text-muted-foreground">
                                    Select an exam and click "Analyze" to view
                                    item analysis.
                                </p>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </AnalyticsLayout>
        </AppLayout>
    );
}
