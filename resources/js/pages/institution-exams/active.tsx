import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { usePermissions } from '@/hooks/use-permissions';
import AppLayout from '@/layouts/app-layout';
import InstitutionExamsLayout from '@/layouts/exams/institution-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ClipboardList, Plus } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Institution Exams',
        href: '/institution-exams/active',
    },
];

export default function Active() {
    const { hasPermission } = usePermissions();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Institution Exams – Active" />

            <InstitutionExamsLayout>
                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <HeadingSmall
                            title="Active Institution Exams"
                            description="Exams currently available in your institution"
                        />
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <span className="inline-block">
                                        <Button
                                            asChild
                                            disabled={
                                                !hasPermission(
                                                    'create-assessments',
                                                )
                                            }
                                        >
                                            <Link href="/institution-exams/create">
                                                <Plus className="mr-2 h-4 w-4" />
                                                Create Exam
                                            </Link>
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

                    <div className="rounded-lg border p-6 text-center">
                        <ClipboardList className="mx-auto h-10 w-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">
                            No active institution exams at the moment.
                        </p>
                        <div className="mt-4">
                            <Button asChild>
                                <a href="/assessments">
                                    View all institution exams
                                </a>
                            </Button>
                        </div>
                    </div>
                </div>
            </InstitutionExamsLayout>
        </AppLayout>
    );
}
