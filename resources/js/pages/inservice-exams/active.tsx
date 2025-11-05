import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import InServiceExamsLayout from '@/layouts/exams/inservice-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { GraduationCap, Plus } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'In-Service Exams',
        href: '/inservice-exams/active',
    },
];

export default function Active() {
    const { auth } = usePage<{
        auth: { user: { roles?: Array<{ name: string }> } };
    }>().props;
    const canCreate = auth.user?.roles?.some((role) =>
        ['System Admin', 'BOP'].includes(role.name),
    );

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
                                <Link href="/in-service/create">
                                    <Plus className="mr-2 h-4 w-4" />
                                    Create National Exam
                                </Link>
                            </Button>
                        )}
                    </div>

                    <div className="rounded-lg border p-6 text-center">
                        <GraduationCap className="mx-auto h-10 w-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">
                            No active in-service exams at the moment.
                        </p>
                        <div className="mt-4">
                            <Button asChild>
                                <a href="/in-service">
                                    View all in-service exams
                                </a>
                            </Button>
                        </div>
                    </div>
                </div>
            </InServiceExamsLayout>
        </AppLayout>
    );
}
