import HeadingSmall from '@/components/heading-small';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import AnalyticsLayout from '@/layouts/analytics/analytics-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Analytics',
        href: '/analytics/question-bank',
    },
];

export default function QuestionBank() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Question Bank Analytics" />

            <AnalyticsLayout>
                <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                    <HeadingSmall
                        title="Question Bank Analytics"
                        description="Performance metrics and statistics for question bank items"
                    />

                    <Card>
                        <CardContent className="py-8 text-center">
                            <p className="text-muted-foreground">
                                Coming soon...
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </AnalyticsLayout>
        </AppLayout>
    );
}

