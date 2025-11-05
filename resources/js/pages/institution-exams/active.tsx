import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import InstitutionExamsLayout from '@/layouts/exams/institution-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { ClipboardList } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Institution Exams',
        href: '/institution-exams/active',
    },
];

export default function Active() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Institution Exams – Active" />

            <InstitutionExamsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Active Institution Exams"
                        description="Exams currently available in your institution"
                    />

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
