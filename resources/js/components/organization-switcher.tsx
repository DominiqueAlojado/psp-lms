import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { SidebarMenuButton, useSidebar } from '@/components/ui/sidebar';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { Building2, Check, ChevronsUpDown, Search } from 'lucide-react';
import { useMemo, useState } from 'react';

const ALL_ORGANIZATIONS_SLUG = 'all-organizations';
const ALL_ORGANIZATIONS_SUPPORTED_PATHS = [
    /^\/support(?:\/.*)?$/,
    /^\/notifications(?:\/.*)?$/,
];

interface OrganizationSwitcherProps {
    className?: string;
}

export function OrganizationSwitcher({
    className,
}: OrganizationSwitcherProps = {}) {
    const { auth } = usePage<SharedData>().props;
    const { organizations, currentOrganization, supportsAllOrganizations } = auth;
    const [search, setSearch] = useState('');
    const { state } = useSidebar();
    const isCollapsed = state === 'collapsed';

    // Sort organizations alphabetically
    const sortedOrganizations = useMemo(() => {
        return [...(organizations ?? [])].sort((a, b) =>
            a.name.localeCompare(b.name),
        );
    }, [organizations]);

    // Filter organizations based on search
    const filteredOrganizations = useMemo(() => {
        const allOrganizationsEntry =
            supportsAllOrganizations
                ? [
                      {
                          id: 0,
                          name: 'All Organizations',
                          slug: ALL_ORGANIZATIONS_SLUG,
                          type: 'aggregate',
                      },
                  ]
                : [];

        if (!search) return [...allOrganizationsEntry, ...sortedOrganizations];

        const searchLower = search.toLowerCase();
        const organizationMatches = sortedOrganizations.filter(
            (org) =>
                org.name.toLowerCase().includes(searchLower) ||
                org.type.toLowerCase().includes(searchLower),
        );

        const includeAllOrganizations =
            supportsAllOrganizations
            && 'all organizations'.includes(searchLower);

        return [
            ...(includeAllOrganizations ? allOrganizationsEntry : []),
            ...organizationMatches,
        ];
    }, [sortedOrganizations, search, supportsAllOrganizations]);

    if (!organizations || organizations.length === 0) {
        return null;
    }

    const handleSwitch = (organizationId: number, organizationSlug?: string) => {
        if (organizationSlug === ALL_ORGANIZATIONS_SLUG) {
            const currentUrl = new URL(window.location.href);
            const supportsAllOrganizationsPath =
                ALL_ORGANIZATIONS_SUPPORTED_PATHS.some((pattern) =>
                    pattern.test(currentUrl.pathname),
                );

            if (!supportsAllOrganizationsPath) {
                router.get('/support', { org: ALL_ORGANIZATIONS_SLUG }, {
                    preserveScroll: true,
                    preserveState: false,
                });
                setSearch('');

                return;
            }

            currentUrl.searchParams.set('org', ALL_ORGANIZATIONS_SLUG);
            router.get(
                `${currentUrl.pathname}${currentUrl.search}`,
                {},
                {
                    preserveScroll: true,
                    preserveState: false,
                },
            );
            setSearch('');

            return;
        }

        router.post(
            `/organization/${organizationId}/switch`,
            {},
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
        setSearch(''); // Clear search after switching
    };

    const dropdownContent = (
        <DropdownMenuContent
            className="w-[320px] rounded-2xl border border-border/80 bg-popover/96 p-1 shadow-[0_24px_52px_-34px_rgb(35_24_74_/_0.32)] dark:shadow-[0_28px_56px_-32px_rgb(0_0_0_/_0.76)]"
            align={isCollapsed ? 'start' : 'start'}
            side={isCollapsed ? 'right' : 'bottom'}
            sideOffset={isCollapsed ? 4 : 8}
        >
            <DropdownMenuLabel className="px-3 pt-2 pb-1 text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                Your Organizations
            </DropdownMenuLabel>
            <DropdownMenuSeparator />

            {/* Search Input */}
            <div className="px-2 py-2">
                <div className="relative">
                    <Search className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                    <Input
                        placeholder="Search organizations..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        onKeyDown={(event) => event.stopPropagation()}
                        className="h-10 rounded-xl bg-background pl-9"
                    />
                </div>
            </div>

            <DropdownMenuSeparator />

            {/* Organizations List */}
            <div className="max-h-[300px] overflow-y-auto">
                {filteredOrganizations.length === 0 ? (
                    <div className="px-2 py-6 text-center text-sm text-muted-foreground">
                        No organizations found
                    </div>
                ) : (
                    filteredOrganizations.map((organization) => (
                        <DropdownMenuItem
                            key={organization.id}
                            onClick={() =>
                                handleSwitch(organization.id, organization.slug)
                            }
                            className="cursor-pointer rounded-xl px-3 py-2.5 focus:bg-accent/85"
                        >
                            <div className="flex w-full items-center justify-between gap-2">
                                <div className="flex min-w-0 flex-1 items-center gap-2">
                                    <div className="flex size-8 shrink-0 items-center justify-center rounded-xl bg-accent/88 text-primary shadow-[0_10px_22px_-18px_rgb(96_44_193_/_0.34)]">
                                        <Building2 className="h-4 w-4 shrink-0" />
                                    </div>
                                    <div className="flex min-w-0 flex-col">
                                        <span className="truncate text-sm font-medium">
                                            {organization.name}
                                        </span>
                                        <span className="text-xs text-muted-foreground capitalize">
                                            {organization.type}
                                        </span>
                                    </div>
                                </div>
                                {(currentOrganization?.id === organization.id
                                    || currentOrganization?.slug === organization.slug) && (
                                    <Check className="h-4 w-4 shrink-0 text-primary" />
                                )}
                            </div>
                        </DropdownMenuItem>
                    ))
                )}
            </div>
        </DropdownMenuContent>
    );

    // When sidebar is collapsed, show only icon with tooltip
    if (isCollapsed) {
        return (
            <DropdownMenu>
                <TooltipProvider delayDuration={0}>
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <DropdownMenuTrigger asChild>
                                <SidebarMenuButton
                                    size="lg"
                                    className={`rounded-[1.25rem] border border-sidebar-border/80 bg-background/88 shadow-[0_14px_28px_-24px_rgb(35_24_74_/_0.22)] data-[state=open]:border-sidebar-border/90 data-[state=open]:bg-sidebar-accent/88 data-[state=open]:text-sidebar-accent-foreground dark:shadow-[0_18px_32px_-24px_rgb(0_0_0_/_0.58)] ${className || ''}`}
                                >
                                    <Building2 className="h-4 w-4" />
                                    <span className="sr-only">
                                        Switch Organization
                                    </span>
                                </SidebarMenuButton>
                            </DropdownMenuTrigger>
                        </TooltipTrigger>
                        <TooltipContent
                            side="right"
                            className="flex items-center gap-2"
                        >
                            <span className="font-medium">
                                {currentOrganization?.name}
                            </span>
                            <span className="text-xs text-muted-foreground capitalize">
                                ({currentOrganization?.type})
                            </span>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
                {dropdownContent}
            </DropdownMenu>
        );
    }

    // When sidebar is expanded, show full button
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <SidebarMenuButton
                    size="lg"
                    className={`w-full rounded-[1.35rem] border border-sidebar-border/80 bg-background/88 px-3 shadow-[0_16px_32px_-28px_rgb(35_24_74_/_0.2)] data-[state=open]:border-sidebar-border/90 data-[state=open]:bg-sidebar-accent/88 data-[state=open]:text-sidebar-accent-foreground dark:shadow-[0_18px_36px_-26px_rgb(0_0_0_/_0.58)] ${className || ''}`}
                >
                    <div className="flex min-w-0 flex-1 items-center gap-2 overflow-hidden">
                        <div className="flex size-8 shrink-0 items-center justify-center rounded-xl bg-accent/88 text-primary shadow-[0_10px_22px_-18px_rgb(96_44_193_/_0.34)]">
                            <Building2 className="h-4 w-4 shrink-0" />
                        </div>
                        <div className="flex min-w-0 flex-1 flex-col items-start">
                            <span className="truncate text-sm font-medium">
                                {currentOrganization?.name ??
                                    'Select organization...'}
                            </span>
                            {currentOrganization && (
                                <span className="text-xs text-muted-foreground capitalize">
                                    {currentOrganization.type}
                                </span>
                            )}
                        </div>
                    </div>
                    <ChevronsUpDown className="ml-auto h-4 w-4 shrink-0 opacity-50" />
                </SidebarMenuButton>
            </DropdownMenuTrigger>
            {dropdownContent}
        </DropdownMenu>
    );
}
