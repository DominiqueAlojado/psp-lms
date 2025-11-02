import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { Building2, Check, ChevronsUpDown } from 'lucide-react';

export function OrganizationSwitcher() {
    const { auth } = usePage<SharedData>().props;
    const { organizations, currentOrganization } = auth;

    if (!organizations || organizations.length === 0) {
        return null;
    }

    const handleSwitch = (organizationId: number, organizationSlug: string) => {
        router.post(`/organization/${organizationId}/switch`, {}, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="outline"
                    role="combobox"
                    className="h-9 w-[200px] justify-between"
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
            <DropdownMenuContent className="w-[200px]" align="start">
                <DropdownMenuLabel>Your Organizations</DropdownMenuLabel>
                <DropdownMenuSeparator />
                {organizations.map((organization) => (
                    <DropdownMenuItem
                        key={organization.id}
                        onClick={() => handleSwitch(organization.id, organization.slug)}
                        className="cursor-pointer"
                    >
                        <div className="flex w-full items-center justify-between">
                            <div className="flex items-center gap-2 overflow-hidden">
                                <Building2 className="h-4 w-4 shrink-0" />
                                <span className="truncate text-sm">
                                    {organization.name}
                                </span>
                            </div>
                            {currentOrganization?.id === organization.id && (
                                <Check className="h-4 w-4 shrink-0" />
                            )}
                        </div>
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

