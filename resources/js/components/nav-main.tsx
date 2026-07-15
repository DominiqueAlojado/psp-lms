import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { preserveOrgParam, resolveUrl } from '@/lib/utils';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const page = usePage<SharedData>();
    const { auth } = page.props;
    const currentOrgSlug = auth.currentOrganization?.slug;

    return (
        <SidebarGroup className="px-2 py-0.5">
            <SidebarGroupLabel>Platform</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => {
                    const hrefWithOrg = preserveOrgParam(
                        item.href,
                        currentOrgSlug,
                    );
                    return (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                isActive={page.url.startsWith(
                                    resolveUrl(item.href),
                                )}
                                tooltip={{ children: item.title }}
                                className="font-medium"
                            >
                                <Link href={hrefWithOrg} prefetch>
                                    {item.icon && <item.icon />}
                                    <span>{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
