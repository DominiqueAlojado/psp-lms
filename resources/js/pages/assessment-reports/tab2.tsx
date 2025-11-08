import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import AssessmentReportsLayout from '@/layouts/assessment-reports/assessment-reports-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { FileBarChart } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Assessment Reports',
        href: '/assessment-reports/tab2',
    },
];

export default function AssessmentReportsTab2() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Assessment Reports - Tab 2" />

            <AssessmentReportsLayout>
                <Card>
                    <CardContent className="p-12 text-center">
                        <FileBarChart className="mx-auto h-12 w-12 text-muted-foreground" />
                        <h3 className="mt-4 text-lg font-semibold">
                            Tab 2 Content
                        </h3>
                        <p className="mt-2 text-sm text-muted-foreground">
                            This tab is currently empty. Content will be added
                            here.
                        </p>
                    </CardContent>
                </Card>
            </AssessmentReportsLayout>
        </AppLayout>
    );
}

