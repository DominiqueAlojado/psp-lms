import HeadingSmall from '@/components/heading-small';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import AnalyticsLayout from '@/layouts/analytics/analytics-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Analytics',
        href: '/analytics/topic-performance',
    },
];

export default function TopicPerformance() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Topic Performance" />

            <AnalyticsLayout>
                <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                    <HeadingSmall
                        title="Topic Performance"
                        description="Analyze performance across different topics"
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

