import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { usePermissions } from '@/hooks/use-permissions';
import { cn, isSameUrl, preserveOrgParam, resolveUrl } from '@/lib/utils';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Active',
        href: { url: '/inservice-exams/active', method: 'get' },
        icon: null,
    },
    {
        title: 'Drafts',
        href: { url: '/inservice-exams/drafts', method: 'get' },
        icon: null,
    },
];

export default function InServiceExamsLayout({ children }: PropsWithChildren) {
    const { hasPermission } = usePermissions();
    const { auth } = usePage<SharedData>().props;
    const currentOrgSlug = auth.currentOrganization?.slug;

    // When server-side rendering, we only render the layout on the client...
    if (typeof window === 'undefined') {
        return null;
    }

    const currentPath = window.location.pathname;

    // Filter sidebar nav items based on permissions (kept for future extensibility)
    const filteredSidebarNavItems = sidebarNavItems.filter((item) => {
        if (!item.permission) {
            return true;
        }
        return hasPermission(item.permission);
    });

    return (
        <div className="px-4 py-6">
            <Heading
                title="In-Service Exams"
                description="Manage national in-service exams (active and drafts)"
            />

            <div className="flex flex-col lg:flex-row lg:space-x-12">
                <aside className="w-full max-w-xl lg:w-48">
                    <nav className="flex flex-col space-y-1 space-x-0">
                        {filteredSidebarNavItems.map((item, index) => {
                            const hrefWithOrg = preserveOrgParam(
                                item.href,
                                currentOrgSlug,
                            );
                            return (
                                <Button
                                    key={`${resolveUrl(item.href)}-${index}`}
                                    size="sm"
                                    variant="ghost"
                                    asChild
                                    className={cn('w-full justify-start rounded-xl', {
                                        'bg-accent/85 text-foreground shadow-[0_14px_28px_-24px_rgb(96_44_193_/_0.24)]': isSameUrl(
                                            currentPath,
                                            item.href,
                                        ),
                                    })}
                                >
                                    <Link href={hrefWithOrg}>
                                        {item.icon && (
                                            <item.icon className="h-4 w-4" />
                                        )}
                                        {item.title}
                                    </Link>
                                </Button>
                            );
                        })}
                    </nav>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className={cn('flex-1')}>
                    <section className={cn('space-y-12')}>{children}</section>
                </div>
            </div>
        </div>
    );
}
