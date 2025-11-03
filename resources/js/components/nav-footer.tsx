import { Icon } from '@/components/icon';
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { resolveUrl } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { type ComponentPropsWithoutRef } from 'react';

export function NavFooter({
    items,
    className,
    ...props
}: ComponentPropsWithoutRef<typeof SidebarGroup> & {
    items: NavItem[];
}) {
    const page = usePage();

    return (
        <SidebarGroup
            {...props}
            className={`group-data-[collapsible=icon]:p-0 ${className || ''}`}
        >
            <SidebarGroupContent>
                <SidebarMenu>
                    {items.map((item) => {
                        const isExternal =
                            item.href.startsWith('http://') ||
                            item.href.startsWith('https://');

                        // Check if current page URL starts with this menu item's href
                        const isActive = !isExternal && page.url.startsWith(resolveUrl(item.href));

                        return (
                            <SidebarMenuItem key={item.title}>
                                <SidebarMenuButton
                                    asChild
                                    isActive={isActive}
                                    tooltip={{ children: item.title }}
                                    className="text-neutral-600 hover:text-neutral-800 dark:text-neutral-300 dark:hover:text-neutral-100"
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
                                        <Link href={item.href}>
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
