import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/app-layout';
import InServiceExamsLayout from '@/layouts/exams/inservice-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Copy, GraduationCap, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'In-Service Exams',
        href: '/inservice-exams/active',
    },
];

interface Exam {
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
    created_by: string | null;
    updated_at: string;
}

interface PaginatedExams {
    data: Exam[];
    total: number;
}

interface PageProps {
    exams?: PaginatedExams;
    filters?: {
        search?: string;
    };
    [key: string]: unknown;
}

export default function Active() {
    const pageProps = usePage<PageProps>().props;
    const { auth, exams, filters } = pageProps;

    // Debug: Check what we're receiving
    if (!exams) {
        console.warn('Exams data is missing from props:', pageProps);
    }

    const canCreate = auth?.user?.roles?.some((role) =>
        ['System Admin', 'BOP'].includes(role.name),
    );
    const [duplicateExam, setDuplicateExam] = useState<Exam | null>(null);
    const [duplicateTitle, setDuplicateTitle] = useState('');
    const [submittingDuplicate, setSubmittingDuplicate] = useState(false);

    const openDuplicateModal = (exam: Exam) => {
        setDuplicateExam(exam);
        setDuplicateTitle(`${exam.title} (Copy)`);
    };

    const closeDuplicateModal = () => {
        setDuplicateExam(null);
        setDuplicateTitle('');
        setSubmittingDuplicate(false);
    };

    const handleDuplicate = () => {
        if (!duplicateExam || submittingDuplicate) {
            return;
        }

        setSubmittingDuplicate(true);
        router.post(
            `/inservice-exams/${duplicateExam.id}/duplicate`,
            {
                title: duplicateTitle.trim() || undefined,
            },
            {
                onFinish: closeDuplicateModal,
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="In-Service Exams – Active" />

            <InServiceExamsLayout>
                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <HeadingSmall
                            title="Active In-Service Exams"
                            description="Exams currently available to take"
                        />
                        {canCreate && (
                            <Button asChild>
                                <Link href="/inservice-exams/create">
                                    <Plus className="mr-2 h-4 w-4" />
                                    Create National Exam
                                </Link>
                            </Button>
                        )}
                    </div>

                    {!exams || exams.data.length === 0 ? (
                        <div className="rounded-lg border p-6 text-center">
                            <GraduationCap className="mx-auto h-10 w-10 text-muted-foreground" />
                            <p className="mt-2 text-sm text-muted-foreground">
                                No active in-service exams at the moment.
                            </p>
                            <div className="mt-4">
                                <Button asChild>
                                    <Link href="/inservice-exams">
                                        View all in-service exams
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {exams?.data?.map((exam) => (
                                <div
                                    key={exam.id}
                                    className="rounded-lg border p-4 hover:bg-muted/50"
                                >
                                    <div className="flex items-start justify-between">
                                        <div className="flex-1">
                                            <div className="flex items-center gap-2">
                                                <h3 className="font-semibold">
                                                    {exam.title}
                                                </h3>
                                                <Badge
                                                    variant="outline"
                                                    className="text-xs"
                                                >
                                                    {exam.exam_year} –{' '}
                                                    {exam.exam_period}
                                                </Badge>
                                                {exam.is_available && (
                                                    <Badge className="bg-green-600">
                                                        Available
                                                    </Badge>
                                                )}
                                            </div>
                                            {exam.description && (
                                                <p className="mt-1 text-sm text-muted-foreground">
                                                    {exam.description}
                                                </p>
                                            )}
                                            <div className="mt-2 flex flex-wrap gap-4 text-sm text-muted-foreground">
                                                <span>
                                                    {exam.questions_count}{' '}
                                                    questions
                                                </span>
                                                <span>
                                                    {exam.total_points} points
                                                </span>
                                                {exam.duration_minutes && (
                                                    <span>
                                                        {exam.duration_minutes}{' '}
                                                        min
                                                    </span>
                                                )}
                                                <span>
                                                    Pass: {exam.passing_score}
                                                </span>
                                                {exam.created_by && (
                                                    <span>
                                                        By {exam.created_by}
                                                    </span>
                                                )}
                                            </div>
                                            {exam.scheduled_date && (
                                                <div className="mt-2 text-xs text-muted-foreground">
                                                    Available from:{' '}
                                                    {exam.scheduled_date}
                                                </div>
                                            )}
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Badge className="bg-green-600">
                                                Published
                                            </Badge>
                                            {canCreate && (
                                                <>
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipTrigger
                                                                asChild
                                                            >
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    asChild
                                                                >
                                                                    <Link
                                                                        href={`/inservice-exams/${exam.id}/edit`}
                                                                    >
                                                                        <Pencil className="h-4 w-4" />
                                                                    </Link>
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>Edit exam</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipTrigger
                                                                asChild
                                                            >
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() =>
                                                                        openDuplicateModal(
                                                                            exam,
                                                                        )
                                                                    }
                                                                >
                                                                    <Copy className="h-4 w-4 text-muted-foreground" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>
                                                                    Duplicate
                                                                    exam
                                                                </p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipTrigger
                                                                asChild
                                                            >
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => {
                                                                        if (
                                                                            confirm(
                                                                                'Are you sure you want to delete this exam?',
                                                                            )
                                                                        ) {
                                                                            router.delete(
                                                                                `/inservice-exams/${exam.id}`,
                                                                            );
                                                                        }
                                                                    }}
                                                                >
                                                                    <Trash2 className="h-4 w-4 text-destructive" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>
                                                                    Delete exam
                                                                </p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>
                                                </>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </InServiceExamsLayout>

            <Dialog
                open={duplicateExam !== null}
                onOpenChange={(open) =>
                    !open ? closeDuplicateModal() : undefined
                }
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Duplicate Exam</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-2">
                        <Label htmlFor="duplicate-exam-title">
                            New Exam Title
                        </Label>
                        <Input
                            id="duplicate-exam-title"
                            value={duplicateTitle}
                            onChange={(e) => setDuplicateTitle(e.target.value)}
                            autoFocus
                        />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={closeDuplicateModal}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="button"
                            onClick={handleDuplicate}
                            disabled={submittingDuplicate}
                        >
                            {submittingDuplicate ? 'Duplicating…' : 'Duplicate'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
