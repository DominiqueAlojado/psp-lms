import HeadingSmall from '@/components/heading-small';
import { StatCard } from '@/components/stat-card';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
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
import { preserveOrgParam } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Filter, MessageSquareText, Search, ShieldCheck, Trash2 } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Feedback',
        href: '/feedback',
    },
    {
        title: 'Manage',
        href: '/feedback/manage',
    },
];

interface FeedbackEntry {
    id: number;
    organization_name: string | null;
    user_name: string | null;
    user_email: string | null;
    overall_rating: number;
    content_rating: number;
    support_rating: number;
    usability_rating: number;
    context: string | null;
    module_name: string | null;
    page_url: string | null;
    comment: string;
    would_recommend: boolean | null;
    created_at: string;
    created_at_human: string;
}

interface PaginatedEntries {
    data: FeedbackEntry[];
    total: number;
    current_page: number;
    last_page: number;
}

interface PageProps {
    entries: PaginatedEntries;
    summary: {
        total_feedback: number;
        average_overall: number;
        average_content: number;
        recommendation_rate: number;
    };
    filters: {
        search?: string;
        module_name?: string;
        would_recommend?: string;
    };
    modules: string[];
    canDeleteFeedback: boolean;
    isAllOrganizationsContext: boolean;
}

export default function ManageFeedback({
    entries,
    summary,
    filters,
    modules,
    canDeleteFeedback,
    isAllOrganizationsContext,
}: PageProps) {
    const { auth } = usePage<SharedData>().props;
    const currentOrgSlug = auth.currentOrganization?.slug;
    const [search, setSearch] = useState(filters.search || '');
    const [moduleName, setModuleName] = useState(filters.module_name || 'all');
    const [wouldRecommend, setWouldRecommend] = useState(filters.would_recommend || 'all');
    const [deletingEntry, setDeletingEntry] = useState<FeedbackEntry | null>(null);

    const applyFilters = () => {
        router.get(
            preserveOrgParam('/feedback/manage', currentOrgSlug),
            {
                search: search || undefined,
                module_name: moduleName !== 'all' ? moduleName : undefined,
                would_recommend: wouldRecommend !== 'all' ? wouldRecommend : undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const clearFilters = () => {
        setSearch('');
        setModuleName('all');
        setWouldRecommend('all');

        router.get(
            preserveOrgParam('/feedback/manage', currentOrgSlug),
            {},
            { preserveState: true, preserveScroll: true },
        );
    };

    const confirmDelete = () => {
        if (!deletingEntry) return;

        router.delete(
            preserveOrgParam(`/feedback/${deletingEntry.id}`, currentOrgSlug),
            {
                preserveScroll: true,
                onSuccess: () => setDeletingEntry(null),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Manage Feedback" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <HeadingSmall
                        title="Manage Feedback"
                        description="Review submitted feedback across the current organization scope and moderate entries when needed."
                    />
                    <Button asChild variant="outline">
                        <Link href={preserveOrgParam('/feedback', currentOrgSlug)}>
                            Back to Feedback
                        </Link>
                    </Button>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        title="Feedback Entries"
                        value={summary.total_feedback}
                        description="All submissions in this scope"
                        icon={MessageSquareText}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Overall Average"
                        value={summary.average_overall > 0 ? `${summary.average_overall.toFixed(1)}/4` : 'N/A'}
                        description="Average platform experience"
                        icon={ShieldCheck}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Content Average"
                        value={summary.average_content > 0 ? `${summary.average_content.toFixed(1)}/4` : 'N/A'}
                        description="Average content quality"
                        icon={Filter}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Recommend Rate"
                        value={`${summary.recommendation_rate.toFixed(1)}%`}
                        description="Would recommend"
                        icon={ShieldCheck}
                        iconColor="text-primary"
                    />
                </div>

                {isAllOrganizationsContext && (
                    <Card>
                        <CardContent className="p-6 text-sm text-muted-foreground">
                            You are reviewing feedback across all organizations you can access.
                        </CardContent>
                    </Card>
                )}

                <Card className="overflow-hidden border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))]">
                    <CardHeader className="pb-3">
                        <CardTitle>Filter Feedback</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-3 lg:grid-cols-[1.4fr_1fr_1fr_auto_auto]">
                        <div className="relative">
                            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Search comments, context, or module"
                                className="pl-9"
                            />
                        </div>
                        <Select value={moduleName} onValueChange={setModuleName}>
                            <SelectTrigger>
                                <SelectValue placeholder="All modules" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All modules</SelectItem>
                                {modules.map((module) => (
                                    <SelectItem key={module} value={module}>
                                        {module}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select value={wouldRecommend} onValueChange={setWouldRecommend}>
                            <SelectTrigger>
                                <SelectValue placeholder="All recommendations" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All recommendations</SelectItem>
                                <SelectItem value="true">Would recommend</SelectItem>
                                <SelectItem value="false">Would not recommend</SelectItem>
                            </SelectContent>
                        </Select>
                        <Button type="button" onClick={applyFilters}>
                            Apply
                        </Button>
                        <Button type="button" variant="outline" onClick={clearFilters}>
                            Clear
                        </Button>
                    </CardContent>
                </Card>

                <Card className="overflow-hidden border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))]">
                    <CardHeader className="pb-3">
                        <CardTitle>Feedback Queue</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Submitted</TableHead>
                                        <TableHead>Organization</TableHead>
                                        <TableHead>User</TableHead>
                                        <TableHead>Module</TableHead>
                                        <TableHead>Comment</TableHead>
                                        <TableHead>Ratings</TableHead>
                                        <TableHead>Recommendation</TableHead>
                                        {canDeleteFeedback && <TableHead className="w-[96px]">Actions</TableHead>}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {entries.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={canDeleteFeedback ? 8 : 7}
                                                className="py-10 text-center text-sm text-muted-foreground"
                                            >
                                                No feedback entries matched the current filters.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        entries.data.map((entry) => (
                                            <TableRow key={entry.id}>
                                                <TableCell className="align-top text-sm text-muted-foreground">
                                                    <div>{entry.created_at}</div>
                                                    <div>{entry.created_at_human}</div>
                                                </TableCell>
                                                <TableCell className="align-top">
                                                    {entry.organization_name ?? 'No organization'}
                                                </TableCell>
                                                <TableCell className="align-top">
                                                    <div className="font-medium">{entry.user_name ?? 'Unknown user'}</div>
                                                    {entry.user_email && (
                                                        <div className="text-xs text-muted-foreground">
                                                            {entry.user_email}
                                                        </div>
                                                    )}
                                                </TableCell>
                                                <TableCell className="align-top">
                                                    {entry.module_name ? (
                                                        <Badge variant="outline">{entry.module_name}</Badge>
                                                    ) : (
                                                        <span className="text-sm text-muted-foreground">General</span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="min-w-[20rem] align-top">
                                                    <div className="space-y-2">
                                                        <p className="text-sm leading-6 text-foreground">{entry.comment}</p>
                                                        {entry.context && (
                                                            <p className="text-xs text-muted-foreground">
                                                                Context: {entry.context}
                                                            </p>
                                                        )}
                                                        {entry.page_url && (
                                                            <p className="text-xs text-muted-foreground">
                                                                Page: {entry.page_url}
                                                            </p>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="align-top text-sm">
                                                    <div>Overall: {entry.overall_rating}/4</div>
                                                    <div>Content: {entry.content_rating}/4</div>
                                                    <div>Support: {entry.support_rating}/4</div>
                                                    <div>Usability: {entry.usability_rating}/4</div>
                                                </TableCell>
                                                <TableCell className="align-top">
                                                    {entry.would_recommend === null ? (
                                                        <Badge variant="outline">No answer</Badge>
                                                    ) : entry.would_recommend ? (
                                                        <Badge variant="secondary">Would recommend</Badge>
                                                    ) : (
                                                        <Badge variant="outline">Would not recommend</Badge>
                                                    )}
                                                </TableCell>
                                                {canDeleteFeedback && (
                                                    <TableCell className="align-top">
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => setDeletingEntry(entry)}
                                                        >
                                                            <Trash2 className="h-4 w-4 text-destructive" />
                                                        </Button>
                                                    </TableCell>
                                                )}
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <AlertDialog
                open={!!deletingEntry}
                onOpenChange={(open) => !open && setDeletingEntry(null)}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Delete feedback entry?</AlertDialogTitle>
                        <AlertDialogDescription>
                            This removes the selected feedback entry from the feedback module.
                            Activity log records will remain for audit history.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={confirmDelete}
                            className="bg-destructive text-white hover:bg-destructive/90"
                        >
                            Delete
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
