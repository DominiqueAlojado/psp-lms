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
import { dashboard } from '@/routes';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    Award,
    BarChart3,
    BookOpen,
    Building2,
    ClipboardList,
    FileText,
    Folder,
    FolderOpen,
    GraduationCap,
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
    },
    {
        title: 'My Grades',
        href: '/my-grades',
        icon: Award,
    },
    {
        title: 'My Assignments',
        href: '/my-assignments',
        icon: Pencil,
        excludeOrgTypes: ['national'], // Hide when in national org
    },
    {
        title: 'Learning Resources',
        href: '/resources',
        icon: FolderOpen,
    },
    {
        title: 'Announcements',
        href: '/announcements',
        icon: Megaphone,
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
        permission: 'view-assessments',
    },
    {
        title: 'Institution Exams',
        href: '/institution-exams',
        icon: ClipboardList,
        permission: 'view-assessments',
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Residents',
        href: '/residents',
        icon: Users,
        permission: 'view-residents',
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
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { hasPermission } = usePermissions();
    const { auth } = usePage<SharedData>().props;
    const currentOrganization = auth.currentOrganization;

    // Filter main nav items based on permissions and organization type
    const filteredMainNavItems = mainNavItems.filter((item) => {
        // Hide "My Exams", "My Grades", and "My Assignments" for staff/admins (show only for residents)
        if ((item.title === 'My Exams' || item.title === 'My Grades' || item.title === 'My Assignments') && hasPermission('view-residents')) {
            return false;
        }

        // Hide items with excludeOrgTypes matching current org
        if (item.excludeOrgTypes && currentOrganization?.type && item.excludeOrgTypes.includes(currentOrganization.type)) {
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
                            <Link href={dashboard()} prefetch>
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
