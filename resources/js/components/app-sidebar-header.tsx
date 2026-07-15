import { Breadcrumbs } from '@/components/breadcrumbs';
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
                <SidebarTrigger className="-ml-1 rounded-2xl border border-border/70 bg-background/80 shadow-[0_12px_28px_-24px_rgb(35_24_74_/_0.28)]" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
        </header>
    );
}
