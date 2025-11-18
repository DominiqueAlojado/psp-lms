import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { usePermissions } from '@/hooks/use-permissions';
import { cn, isSameUrl, resolveUrl } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { type PropsWithChildren, useState } from 'react';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Exam Analytics',
        href: { url: '/analytics/exam-analytics', method: 'get' },
        icon: null,
    },
    {
        title: 'Topic Performance',
        href: { url: '/analytics/topic-performance', method: 'get' },
        icon: null,
    },
    {
        title: 'Question Bank',
        href: { url: '/analytics/question-bank', method: 'get' },
        icon: null,
    },
    {
        title: 'Category Performance',
        href: { url: '/analytics/category-performance', method: 'get' },
        icon: null,
    },
    {
        title: 'Trends',
        href: { url: '/analytics/trends', method: 'get' },
        icon: null,
    },
];

export default function AnalyticsLayout({
    children,
}: PropsWithChildren) {
    const { hasPermission } = usePermissions();
    const [isCollapsed, setIsCollapsed] = useState(false);

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
                title="Analytics"
                description="Deep insights and analysis of exam performance and trends"
            />

            <div className="flex flex-col lg:flex-row lg:space-x-12">
                <aside
                    className={cn(
                        'w-full transition-all duration-300',
                        isCollapsed ? 'lg:w-12' : 'max-w-xl lg:w-48',
                    )}
                >
                    <div className="flex items-center justify-between gap-2">
                        <nav
                            className={cn(
                                'flex flex-col space-y-1 space-x-0 flex-1',
                                isCollapsed && 'lg:hidden',
                            )}
                        >
                            {filteredSidebarNavItems.map((item, index) => (
                                <Button
                                    key={`${resolveUrl(item.href)}-${index}`}
                                    size="sm"
                                    variant="ghost"
                                    asChild
                                    className={cn('w-full justify-start', {
                                        'bg-muted': isSameUrl(
                                            currentPath,
                                            item.href,
                                        ),
                                    })}
                                >
                                    <Link href={item.href}>
                                        {item.icon && (
                                            <item.icon className="h-4 w-4" />
                                        )}
                                        {item.title}
                                    </Link>
                                </Button>
                            ))}
                        </nav>
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setIsCollapsed(!isCollapsed)}
                            className="hidden h-8 w-8 p-0 lg:flex"
                        >
                            {isCollapsed ? (
                                <ChevronRight className="h-4 w-4" />
                            ) : (
                                <ChevronLeft className="h-4 w-4" />
                            )}
                        </Button>
                    </div>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className="flex-1">
                    <section className="space-y-6">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}

