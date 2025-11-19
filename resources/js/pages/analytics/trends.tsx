import HeadingSmall from '@/components/heading-small';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import AnalyticsLayout from '@/layouts/analytics/analytics-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Analytics',
        href: '/analytics/trends',
    },
];

export default function Trends() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Trends" />

            <AnalyticsLayout>
                <div className="space-y-6">
                    <div className="flex items-start justify-between gap-4">
                        <HeadingSmall
                            title="Trends"
                            description="Performance trends over time"
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

