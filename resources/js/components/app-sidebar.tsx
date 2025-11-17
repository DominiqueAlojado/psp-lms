import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { OrganizationSwitcher } from '@/components/organization-switcher';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { usePermissions } from '@/hooks/use-permissions';
import { preserveOrgParam } from '@/lib/utils';
import { dashboard } from '@/routes';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    Award,
    BarChart3,
    Building2,
    CalendarDays,
    ClipboardList,
    FileText,
    FolderOpen,
    GraduationCap,
    HelpCircle,
    LayoutGrid,
    Megaphone,
    Pencil,
    UserCog,
    Users,
} from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'My Exams',
        href: '/resident-exams',
        icon: FileText,
        permission: 'take-assessments',
    },
    {
        title: 'My Grades',
        href: '/my-grades',
        icon: Award,
        permission: 'view-resident-grades',
    },
    {
        title: 'My Assignments',
        href: '/my-assignments',
        icon: Pencil,
        permission: 'view-resident-assignments',
        excludeOrgTypes: ['national'], // Hide when in national org
    },
    {
        title: 'Learning Resources',
        href: '/resources',
        icon: FolderOpen,
        permission: 'view-materials',
    },
    {
        title: 'Announcements',
        href: '/announcements',
        icon: Megaphone,
        permission: 'view-announcements',
    },
    {
        title: 'Events',
        href: '/events',
        icon: CalendarDays,
        permission: 'view-events',
    },
    {
        title: 'Assignments',
        href: '/assignments',
        icon: Pencil,
        permission: 'view-assignments',
        excludeOrgTypes: ['national'], // Hide when in national org
    },
    {
        title: 'Assessment Reports',
        href: '/assessment-reports',
        icon: BarChart3,
        permission: 'view-assessment-reports',
    },
    {
        title: 'In-Service Exams',
        href: '/inservice-exams',
        icon: GraduationCap,
        permission: 'view-inservice-exams',
    },
    {
        title: 'Institution Exams',
        href: '/institution-exams',
        icon: ClipboardList,
        permission: 'view-institution-exams',
    },
    {
        title: 'Question Bank',
        href: '/question-bank',
        icon: HelpCircle,
        permission: 'view-assessments',
        excludeRoles: ['Resident'],
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Residents',
        href: '/residents',
        icon: Users,
        permission: 'view-residents',
        excludeRoles: ['Resident', 'Training Officer'],
    },
    {
        title: 'Staff',
        href: '/staff',
        icon: UserCog,
        permission: 'view-staff',
    },
    {
        title: 'Institutions',
        href: '/institutions',
        icon: Building2,
        permission: 'view-institutions',
    },
];

export function AppSidebar() {
    const { hasPermission } = usePermissions();
    const { auth } = usePage<SharedData>().props;
    const currentOrganization = auth.currentOrganization;

    // Filter main nav items based on permissions and organization type
    const filteredMainNavItems = mainNavItems.filter((item) => {
        if (
            item.excludeRoles &&
            auth?.roles &&
            item.excludeRoles.some((role) => auth.roles.includes(role))
        ) {
            return false;
        }

        // Hide items with excludeOrgTypes matching current org
        if (
            item.excludeOrgTypes &&
            currentOrganization?.type &&
            item.excludeOrgTypes.includes(currentOrganization.type)
        ) {
            return false;
        }

        // Hide "In-Service Exams" if organization is not national
        if (
            item.title === 'In-Service Exams' &&
            currentOrganization?.type !== 'national'
        ) {
            return false;
        }

        // Hide "Institution Exams" if organization is national
        if (
            item.title === 'Institution Exams' &&
            currentOrganization?.type === 'national'
        ) {
            return false;
        }

        // Hide "Institution Exams" for residents (show only for staff/admins)
        if (
            item.title === 'Institution Exams' &&
            !hasPermission('view-residents')
        ) {
            return false;
        }

        if (!item.permission) {
            return true;
        }
        return hasPermission(item.permission);
    });

    // Filter footer nav items based on permissions
    const filteredFooterNavItems = footerNavItems.filter((item) => {
        if (!item.permission) {
            return true;
        }
        return hasPermission(item.permission);
    });

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link
                                href={preserveOrgParam(
                                    dashboard(),
                                    auth.currentOrganization?.slug,
                                )}
                                prefetch
                            >
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <SidebarMenuItem>
                        <OrganizationSwitcher />
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={filteredMainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={filteredFooterNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
