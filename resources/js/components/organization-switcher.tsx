import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { Building2, Check, ChevronsUpDown, Search } from 'lucide-react';
import { useMemo, useState } from 'react';

interface OrganizationSwitcherProps {
    className?: string;
}

export function OrganizationSwitcher({ className }: OrganizationSwitcherProps = {}) {
    const { auth } = usePage<SharedData>().props;
    const { organizations, currentOrganization } = auth;
    const [search, setSearch] = useState('');

    if (!organizations || organizations.length === 0) {
        return null;
    }

    // Sort organizations alphabetically
    const sortedOrganizations = useMemo(() => {
        return [...organizations].sort((a, b) => a.name.localeCompare(b.name));
    }, [organizations]);

    // Filter organizations based on search
    const filteredOrganizations = useMemo(() => {
        if (!search) return sortedOrganizations;
        
        const searchLower = search.toLowerCase();
        return sortedOrganizations.filter(org => 
            org.name.toLowerCase().includes(searchLower) ||
            org.type.toLowerCase().includes(searchLower)
        );
    }, [sortedOrganizations, search]);

    const handleSwitch = (organizationId: number, organizationSlug: string) => {
        router.post(`/organization/${organizationId}/switch`, {}, {
            preserveScroll: true,
            preserveState: true,
        });
        setSearch(''); // Clear search after switching
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="outline"
                    role="combobox"
                    className={cn("h-9 w-[300px] justify-between", className)}
                >
                    <div className="flex items-center gap-2 overflow-hidden">
                        <Building2 className="h-4 w-4 shrink-0" />
                        <span className="truncate text-sm">
                            {currentOrganization?.name ?? 'Select organization...'}
                        </span>
                    </div>
                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent className="w-[300px]" align="start">
                <DropdownMenuLabel>Your Organizations</DropdownMenuLabel>
                <DropdownMenuSeparator />
                
                {/* Search Input */}
                <div className="px-2 py-2">
                    <div className="relative">
                        <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            placeholder="Search organizations..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="h-9 pl-8"
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
                                onClick={() => handleSwitch(organization.id, organization.slug)}
                                className="cursor-pointer"
                            >
                                <div className="flex w-full items-center justify-between gap-2">
                                    <div className="flex min-w-0 flex-1 items-center gap-2">
                                        <Building2 className="h-4 w-4 shrink-0" />
                                        <div className="flex min-w-0 flex-col">
                                            <span className="truncate text-sm font-medium">
                                                {organization.name}
                                            </span>
                                            <span className="text-xs text-muted-foreground capitalize">
                                                {organization.type}
                                            </span>
                                        </div>
                                    </div>
                                    {currentOrganization?.id === organization.id && (
                                        <Check className="h-4 w-4 shrink-0 text-primary" />
                                    )}
                                </div>
                            </DropdownMenuItem>
                        ))
                    )}
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

