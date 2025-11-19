import HeadingSmall from '@/components/heading-small';
import { Card, CardContent } from '@/components/ui/card';
import AnalyticsLayout from '@/layouts/analytics/analytics-layout';
import AppLayout from '@/layouts/app-layout';
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
                <div className="space-y-6">
                    <div className="flex items-start justify-between gap-4">
                        <HeadingSmall
                            title="Topic Performance"
                            description="Analyze performance across different topics"
                        />
                    </div>

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
