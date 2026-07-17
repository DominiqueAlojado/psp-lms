import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { usePermissions } from '@/hooks/use-permissions';
import { cn, isSameUrl, preserveOrgParam, resolveUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import organization from '@/routes/organization';
import { edit } from '@/routes/profile';
import { show } from '@/routes/two-factor';
import { edit as editPassword } from '@/routes/user-password';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: edit(),
        icon: null,
    },
    {
        title: 'Password',
        href: editPassword(),
        icon: null,
    },
    {
        title: 'Two-Factor Auth',
        href: show(),
        icon: null,
    },
    {
        title: 'Organization',
        href: organization.edit(),
        icon: null,
        permission: 'manage-organization-settings',
        excludeRoles: ['Resident'],
    },
    {
        title: 'Roles & Permissions',
        href: { url: '/settings/roles-permissions', method: 'get' },
        icon: null,
        permission: 'manage-permissions',
    },
    {
        title: 'System Configuration',
        href: { url: '/settings/configurations', method: 'get' },
        icon: null,
        permission: 'manage-system-configurations',
    },
    {
        title: 'Appearance',
        href: editAppearance(),
        icon: null,
    },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { hasPermission } = usePermissions();
    const { auth } = usePage<SharedData>().props;

    // When server-side rendering, we only render the layout on the client...
    if (typeof window === 'undefined') {
        return null;
    }

    const currentPath = window.location.pathname;
    const isOrganizationPage = currentPath.includes('/settings/organization');
    const isRolesPermissionsPage = currentPath.includes(
        '/settings/roles-permissions',
    );
    const isConfigurationsPage = currentPath.includes(
        '/settings/configurations',
    );
    const isWidePage =
        isOrganizationPage || isRolesPermissionsPage || isConfigurationsPage;

    // Filter sidebar nav items based on permissions and excluded roles
    const filteredSidebarNavItems = sidebarNavItems.filter((item) => {
        // Exclude items based on roles
        if (
            item.excludeRoles &&
            auth?.roles &&
            item.excludeRoles.some((role) => auth.roles.includes(role))
        ) {
            return false;
        }

        // If no permission is required, show the item
        if (!item.permission) {
            return true;
        }
        // Otherwise, check if user has the required permission
        return hasPermission(item.permission);
    });

    return (
        <div className="px-4 py-6">
            <Heading
                title="Settings"
                description="Manage your profile and account settings"
            />

            <div className="flex flex-col lg:flex-row lg:space-x-12">
                <aside className="w-full max-w-xl lg:w-48">
                    <nav className="flex flex-col space-y-1 space-x-0">
                        {filteredSidebarNavItems.map((item, index) => (
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
                                <Link
                                    href={preserveOrgParam(
                                        item.href,
                                        auth.currentOrganization?.slug,
                                    )}
                                >
                                    {item.icon && (
                                        <item.icon className="h-4 w-4" />
                                    )}
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div
                    className={cn(
                        'flex-1',
                        isWidePage ? 'md:max-w-7xl' : 'md:max-w-2xl',
                    )}
                >
                    <section
                        className={cn(
                            'space-y-12',
                            isWidePage ? 'max-w-full' : 'max-w-xl',
                        )}
                    >
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
