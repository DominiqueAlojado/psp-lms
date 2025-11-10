import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
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
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Award, ExternalLink, Eye } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Assessment Reports',
        href: '/assessment-reports/by-performance',
    },
];

interface ResidentStats {
    total_exams: number;
    total_institution_exams: number;
    total_national_exams: number;
    average_score: number;
    average_percentage: number;
    total_passed: number;
    total_failed: number;
    pass_rate: number;
    highest_score: number;
    lowest_score: number;
}

interface Resident {
    id: number;
    name: string;
    year_level: string;
    status: string;
    stats: ResidentStats;
}

interface Props {
    residents: Resident[];
}

export default function ByPerformanceReport({ residents }: Props) {
    const [search, setSearch] = useState('');
    const [selectedResident, setSelectedResident] = useState<Resident | null>(
        null,
    );
    const [sheetOpen, setSheetOpen] = useState(false);

    // Filter residents by search
    const filteredResidents = residents.filter((resident) =>
        resident.name.toLowerCase().includes(search.toLowerCase()),
    );

    const handleViewDetails = (resident: Resident) => {
        setSelectedResident(resident);
        setSheetOpen(true);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Performance Report" />

            <AssessmentReportsLayout>
                <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                    {/* Header */}
                    <div className="flex items-start justify-between gap-4">
                        <HeadingSmall
                            title="Performance Report"
                            description="Monitor resident performance and track progress"
                        />
                    </div>

                    {/* Search */}
                    <div className="flex items-center gap-4">
                        <Input
                            placeholder="Search residents..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="max-w-sm"
                        />
                    </div>

                    {/* Residents Table */}
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                Residents ({filteredResidents.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {filteredResidents.length > 0 ? (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Name</TableHead>
                                            <TableHead className="text-center">
                                                Year Level
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Status
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Total Exams
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Average
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Pass Rate
                                            </TableHead>
                                            <TableHead className="text-right">
                                                Actions
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {filteredResidents.map((resident) => (
                                            <TableRow key={resident.id}>
                                                <TableCell className="font-medium">
                                                    {resident.name}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    <Badge variant="outline">
                                                        {resident.year_level}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    <Badge
                                                        variant={
                                                            resident.status ===
                                                            'active'
                                                                ? 'default'
                                                                : 'secondary'
                                                        }
                                                    >
                                                        {resident.status}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {resident.stats.total_exams}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {resident.stats.total_exams >
                                                    0 ? (
                                                        <Badge
                                                            variant={
                                                                resident.stats
                                                                    .average_percentage >=
                                                                75
                                                                    ? 'default'
                                                                    : resident.stats
                                                                            .average_percentage >=
                                                                        60
                                                                      ? 'secondary'
                                                                      : 'destructive'
                                                            }
                                                        >
                                                            {resident.stats.average_percentage.toFixed(
                                                                1,
                                                            )}
                                                            %
                                                        </Badge>
                                                    ) : (
                                                        <span className="text-muted-foreground">
                                                            N/A
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {resident.stats.total_exams >
                                                    0 ? (
                                                        <span>
                                                            {resident.stats.pass_rate.toFixed(
                                                                1,
                                                            )}
                                                            %
                                                        </span>
                                                    ) : (
                                                        <span className="text-muted-foreground">
                                                            N/A
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            handleViewDetails(
                                                                resident,
                                                            )
                                                        }
                                                    >
                                                        <Eye className="mr-2 size-4" />
                                                        View Details
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            ) : (
                                <div className="flex flex-col items-center justify-center py-12">
                                    <Award className="mb-4 size-12 text-muted-foreground" />
                                    <h3 className="mb-2 text-lg font-semibold">
                                        {search
                                            ? 'No residents found'
                                            : 'No residents yet'}
                                    </h3>
                                    <p className="text-center text-sm text-muted-foreground">
                                        {search
                                            ? 'Try adjusting your search criteria'
                                            : 'Residents will appear here once they are added to your organization'}
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Resident Details Sheet */}
                <Sheet open={sheetOpen} onOpenChange={setSheetOpen}>
                    <SheetContent className="w-full overflow-y-auto sm:max-w-2xl">
                        {selectedResident && (
                            <>
                                <SheetHeader>
                                    <SheetTitle className="text-2xl">
                                        {selectedResident.name}
                                    </SheetTitle>
                                    <SheetDescription>
                                        Performance summary and statistics
                                    </SheetDescription>
                                </SheetHeader>

                                <div className="mt-6 space-y-6">
                                    {/* Resident Info */}
                                    <div className="flex gap-2">
                                        <Badge variant="outline">
                                            {selectedResident.year_level}
                                        </Badge>
                                        <Badge
                                            variant={
                                                selectedResident.status ===
                                                'active'
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {selectedResident.status}
                                        </Badge>
                                    </div>

                                    {/* Statistics Cards */}
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <Card>
                                            <CardHeader className="pb-3">
                                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                                    Total Exams
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent>
                                                <div className="text-3xl font-bold">
                                                    {
                                                        selectedResident.stats
                                                            .total_exams
                                                    }
                                                </div>
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    {
                                                        selectedResident.stats
                                                            .total_institution_exams
                                                    }{' '}
                                                    institution,{' '}
                                                    {
                                                        selectedResident.stats
                                                            .total_national_exams
                                                    }{' '}
                                                    national
                                                </p>
                                            </CardContent>
                                        </Card>

                                        <Card>
                                            <CardHeader className="pb-3">
                                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                                    Average Score
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent>
                                                <div className="text-3xl font-bold">
                                                    {selectedResident.stats.average_percentage.toFixed(
                                                        1,
                                                    )}
                                                    %
                                                </div>
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    Across all exams
                                                </p>
                                            </CardContent>
                                        </Card>

                                        <Card>
                                            <CardHeader className="pb-3">
                                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                                    Pass Rate
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent>
                                                <div className="text-3xl font-bold">
                                                    {selectedResident.stats.pass_rate.toFixed(
                                                        1,
                                                    )}
                                                    %
                                                </div>
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    {
                                                        selectedResident.stats
                                                            .total_passed
                                                    }{' '}
                                                    passed,{' '}
                                                    {
                                                        selectedResident.stats
                                                            .total_failed
                                                    }{' '}
                                                    failed
                                                </p>
                                            </CardContent>
                                        </Card>

                                        <Card>
                                            <CardHeader className="pb-3">
                                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                                    Score Range
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent>
                                                <div className="text-3xl font-bold">
                                                    {selectedResident.stats.lowest_score.toFixed(
                                                        0,
                                                    )}
                                                    % -{' '}
                                                    {selectedResident.stats.highest_score.toFixed(
                                                        0,
                                                    )}
                                                    %
                                                </div>
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    Lowest to highest
                                                </p>
                                            </CardContent>
                                        </Card>
                                    </div>

                                    {/* View Full Report Button */}
                                    <Button className="w-full" asChild>
                                        <Link
                                            href={`/assessment-reports/resident/${selectedResident.id}`}
                                        >
                                            <ExternalLink className="mr-2 size-4" />
                                            View Full Report
                                        </Link>
                                    </Button>
                                </div>
                            </>
                        )}
                    </SheetContent>
                </Sheet>
            </AssessmentReportsLayout>
        </AppLayout>
    );
}

