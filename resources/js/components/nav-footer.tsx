import { Icon } from '@/components/icon';
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { preserveOrgParam, resolveUrl } from '@/lib/utils';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { type ComponentPropsWithoutRef } from 'react';

export function NavFooter({
    items,
    className,
    ...props
}: ComponentPropsWithoutRef<typeof SidebarGroup> & {
    items: NavItem[];
}) {
    const page = usePage<SharedData>();
    const { auth } = page.props;
    const currentOrgSlug = auth.currentOrganization?.slug;

    return (
        <SidebarGroup
            {...props}
            className={`group-data-[collapsible=icon]:p-0 ${className || ''}`}
        >
            <SidebarGroupContent>
                <SidebarMenu>
                    {items.map((item) => {
                        const isExternal =
                            typeof item.href === 'string' &&
                            (item.href.startsWith('http://') ||
                                item.href.startsWith('https://'));

                        // Check if current page URL starts with this menu item's href
                        const isActive =
                            !isExternal &&
                            page.url.startsWith(resolveUrl(item.href));

                        const hrefWithOrg = isExternal
                            ? item.href
                            : preserveOrgParam(item.href, currentOrgSlug);

                        return (
                            <SidebarMenuItem key={item.title}>
                                <SidebarMenuButton
                                    asChild
                                    isActive={isActive}
                                    tooltip={{ children: item.title }}
                                    className="font-medium"
                                >
                                    {isExternal ? (
                                        <a
                                            href={resolveUrl(item.href)}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            {item.icon && (
                                                <Icon
                                                    iconNode={item.icon}
                                                    className="h-5 w-5"
                                                />
                                            )}
                                            <span>{item.title}</span>
                                        </a>
                                    ) : (
                                        <Link href={hrefWithOrg}>
                                            {item.icon && (
                                                <Icon
                                                    iconNode={item.icon}
                                                    className="h-5 w-5"
                                                />
                                            )}
                                            <span>{item.title}</span>
                                        </Link>
                                    )}
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        );
                    })}
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    );
}
