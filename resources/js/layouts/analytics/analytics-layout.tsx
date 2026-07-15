import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { usePermissions } from '@/hooks/use-permissions';
import { cn, isSameUrl, resolveUrl } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import {
    BarChart3,
    ChartColumnBig,
    ChartLine,
    ChartNoAxesColumn,
    ChevronLeft,
    ChevronRight,
    Database,
    PieChart,
} from 'lucide-react';
import { type PropsWithChildren, useState } from 'react';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Exam Analytics',
        href: { url: '/analytics/exam-analytics', method: 'get' },
        icon: BarChart3,
    },
    {
        title: 'Item Analysis',
        href: { url: '/analytics/item-analysis', method: 'get' },
        icon: ChartColumnBig,
    },
    {
        title: 'Topic Performance',
        href: { url: '/analytics/topic-performance', method: 'get' },
        icon: PieChart,
    },
    {
        title: 'Question Bank',
        href: { url: '/analytics/question-bank', method: 'get' },
        icon: Database,
    },
    {
        title: 'Category Performance',
        href: { url: '/analytics/category-performance', method: 'get' },
        icon: ChartNoAxesColumn,
    },
    {
        title: 'Trends',
        href: { url: '/analytics/trends', method: 'get' },
        icon: ChartLine,
    },
];

export default function AnalyticsLayout({ children }: PropsWithChildren) {
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
        <div className="flex flex-col lg:flex-row lg:space-x-12">
            <aside
                className={cn(
                    'w-full pt-4 pl-2 transition-all duration-300 lg:pt-6',
                    isCollapsed ? 'lg:w-16' : 'max-w-xl lg:w-56',
                )}
            >
                <div className="flex items-center justify-between gap-2">
                    <nav className="flex flex-1 flex-col space-y-1 space-x-0">
                        {filteredSidebarNavItems.map((item, index) => (
                            <Tooltip key={`${resolveUrl(item.href)}-${index}`}>
                                <TooltipTrigger asChild>
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        asChild
                                        className={cn(
                                            'w-full gap-3',
                                            isCollapsed
                                                ? 'justify-center px-0'
                                                : 'justify-start',
                                            {
                                                'bg-muted': isSameUrl(
                                                    currentPath,
                                                    item.href,
                                                ),
                                            },
                                        )}
                                    >
                                        <Link href={item.href}>
                                            {item.icon && (
                                                <item.icon className="h-4 w-4 shrink-0" />
                                            )}
                                            {!isCollapsed && (
                                                <span>{item.title}</span>
                                            )}
                                        </Link>
                                    </Button>
                                </TooltipTrigger>
                                {isCollapsed && (
                                    <TooltipContent side="right">
                                        {item.title}
                                    </TooltipContent>
                                )}
                            </Tooltip>
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

            <div className="flex min-w-0 flex-1 flex-col">
                <div className="mb-0 p-4">
                    <Heading
                        title="Analytics"
                        description="Deep insights and analysis of exam performance and trends"
                    />
                </div>
                <div className="w-full p-4">{children}</div>
            </div>
        </div>
    );
}
