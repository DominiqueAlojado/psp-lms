import HeadingSmall from '@/components/heading-small';
import { preserveOrgParam } from '@/lib/utils';
import { StatCard } from '@/components/stat-card';
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
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Award, ExternalLink, Eye, Search, TrendingUp, Users } from 'lucide-react';
import { useMemo, useState } from 'react';

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
    const { auth } = usePage<SharedData>().props;
    const [search, setSearch] = useState('');
    const [selectedResident, setSelectedResident] = useState<Resident | null>(
        null,
    );
    const [sheetOpen, setSheetOpen] = useState(false);

    const filteredResidents = residents.filter((resident) =>
        resident.name.toLowerCase().includes(search.toLowerCase()),
    );

    const activeResidents = filteredResidents.filter(
        (resident) => resident.status === 'active',
    ).length;
    const residentsWithAttempts = filteredResidents.filter(
        (resident) => resident.stats.total_exams > 0,
    ).length;
    const averagePassRate = useMemo(() => {
        if (filteredResidents.length === 0) {
            return 0;
        }

        const total = filteredResidents.reduce(
            (sum, resident) => sum + resident.stats.pass_rate,
            0,
        );

        return total / filteredResidents.length;
    }, [filteredResidents]);

    const handleViewDetails = (resident: Resident) => {
        setSelectedResident(resident);
        setSheetOpen(true);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Performance Report" />

            <AssessmentReportsLayout>
                <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                    <HeadingSmall
                        title="Performance Report"
                        description="Monitor resident performance and track progress"
                    />

                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <StatCard
                            title="Residents"
                            value={filteredResidents.length}
                            description="Residents in the current report view"
                            icon={Users}
                            iconColor="text-primary"
                        />
                        <StatCard
                            title="Active"
                            value={activeResidents}
                            description="Residents marked as active"
                            icon={Award}
                            iconColor="text-primary"
                        />
                        <StatCard
                            title="With Attempts"
                            value={residentsWithAttempts}
                            description="Residents with completed assessments"
                            icon={TrendingUp}
                            iconColor="text-primary"
                        />
                        <StatCard
                            title="Average Pass Rate"
                            value={`${averagePassRate.toFixed(1)}%`}
                            description="Average pass rate across visible residents"
                            icon={TrendingUp}
                            iconColor="text-primary"
                        />
                    </div>

                    <Card className="overflow-hidden border-primary/12 bg-[linear-gradient(135deg,color-mix(in_oklab,var(--color-accent)_88%,white)_0%,color-mix(in_oklab,var(--color-card)_96%,var(--color-accent))_100%)] dark:bg-[linear-gradient(135deg,color-mix(in_oklab,var(--color-accent)_72%,black)_0%,color-mix(in_oklab,var(--color-card)_92%,var(--color-accent))_100%)]">
                        <CardContent className="flex flex-col gap-4 p-6 lg:flex-row lg:items-center lg:justify-between">
                            <div className="space-y-1">
                                <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                    Report focus
                                </p>
                                <h3 className="text-2xl font-semibold tracking-[-0.04em] text-foreground">
                                    Resident performance overview
                                </h3>
                                <p className="text-sm leading-6 text-muted-foreground">
                                    Use this report to spot strong performers, residents needing support, and overall cohort movement.
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Badge variant="secondary">
                                    {filteredResidents.length} visible residents
                                </Badge>
                                <Badge variant="outline">
                                    {search ? 'Filtered search' : 'Full list'}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="overflow-hidden border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))]">
                        <CardContent className="space-y-4 pt-6">
                            <div className="space-y-2">
                                <label className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                    Search Residents
                                </label>
                                <div className="relative max-w-sm">
                                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        placeholder="Search residents..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="pl-9"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="overflow-hidden border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))]">
                        <CardHeader className="pb-3">
                            <CardTitle>
                                Residents ({filteredResidents.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {filteredResidents.length > 0 ? (
                                <div className="overflow-x-auto">
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
                                                        {resident.stats.total_exams > 0 ? (
                                                            <Badge
                                                                variant={
                                                                    resident.stats.average_percentage >=
                                                                    75
                                                                        ? 'default'
                                                                        : resident.stats.average_percentage >=
                                                                            60
                                                                          ? 'secondary'
                                                                          : 'destructive'
                                                                }
                                                            >
                                                                {resident.stats.average_percentage.toFixed(1)}%
                                                            </Badge>
                                                        ) : (
                                                            <span className="text-muted-foreground">
                                                                N/A
                                                            </span>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        {resident.stats.total_exams > 0 ? (
                                                            <span>
                                                                {resident.stats.pass_rate.toFixed(1)}%
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
                                                                handleViewDetails(resident)
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
                                </div>
                            ) : (
                                <div className="flex flex-col items-center justify-center py-12">
                                    <Award className="mb-4 size-12 text-muted-foreground" />
                                    <h3 className="mb-2 text-lg font-semibold">
                                        {search ? 'No residents found' : 'No residents yet'}
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

                <Sheet open={sheetOpen} onOpenChange={setSheetOpen}>
                    <SheetContent className="w-full overflow-y-auto sm:max-w-2xl">
                        {selectedResident ? (
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
                                    <div className="flex gap-2">
                                        <Badge variant="outline">
                                            {selectedResident.year_level}
                                        </Badge>
                                        <Badge
                                            variant={
                                                selectedResident.status === 'active'
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {selectedResident.status}
                                        </Badge>
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <StatCard
                                            title="Total Exams"
                                            value={selectedResident.stats.total_exams}
                                            description={`${selectedResident.stats.total_institution_exams} institution, ${selectedResident.stats.total_national_exams} national`}
                                            icon={Award}
                                            iconColor="text-primary"
                                        />
                                        <StatCard
                                            title="Average Score"
                                            value={`${selectedResident.stats.average_percentage.toFixed(1)}%`}
                                            description="Across all completed exams"
                                            icon={TrendingUp}
                                            iconColor="text-primary"
                                        />
                                        <StatCard
                                            title="Pass Rate"
                                            value={`${selectedResident.stats.pass_rate.toFixed(1)}%`}
                                            description={`${selectedResident.stats.total_passed} passed, ${selectedResident.stats.total_failed} failed`}
                                            icon={TrendingUp}
                                            iconColor="text-primary"
                                        />
                                        <StatCard
                                            title="Score Range"
                                            value={`${selectedResident.stats.lowest_score.toFixed(0)}% - ${selectedResident.stats.highest_score.toFixed(0)}%`}
                                            description="Lowest to highest result"
                                            icon={TrendingUp}
                                            iconColor="text-primary"
                                        />
                                    </div>

                                    <Button className="w-full" asChild>
                                        <Link
                                            href={preserveOrgParam(
                                                `/assessment-reports/resident/${selectedResident.id}`,
                                                auth.currentOrganization?.slug,
                                            )}
                                        >
                                            <ExternalLink className="mr-2 size-4" />
                                            View Full Report
                                        </Link>
                                    </Button>
                                </div>
                            </>
                        ) : null}
                    </SheetContent>
                </Sheet>
            </AssessmentReportsLayout>
        </AppLayout>
    );
}
