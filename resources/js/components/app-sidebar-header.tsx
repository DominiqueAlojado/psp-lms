import { Breadcrumbs } from '@/components/breadcrumbs';
import { NotificationCenter } from '@/components/notification-center';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    return (
        <header className="flex h-18 shrink-0 items-center gap-3 border-b border-border/60 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-14 md:px-5">
            <div className="flex items-center gap-3">
                <SidebarTrigger className="-ml-1 rounded-2xl border border-border/80 bg-background/88 shadow-[0_14px_30px_-24px_rgb(35_24_74_/_0.2)] dark:shadow-[0_18px_32px_-24px_rgb(0_0_0_/_0.58)]" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <div className="ml-auto">
                <NotificationCenter />
            </div>
        </header>
    );
}
