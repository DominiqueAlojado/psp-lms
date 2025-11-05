import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import InServiceExamsLayout from '@/layouts/exams/inservice-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { GraduationCap } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'In-Service Exams',
        href: '/inservice-exams/active',
    },
];

export default function Active() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="In-Service Exams – Active" />

            <InServiceExamsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Active In-Service Exams"
                        description="Exams currently available to take"
                    />

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
