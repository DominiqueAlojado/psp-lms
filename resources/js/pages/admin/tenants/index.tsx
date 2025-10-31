import admin from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '#',
    },
    {
        title: 'Tenants',
        href: admin.tenants.index().url,
    },
];

interface Tenant {
    id: number;
    name: string;
    domain: string;
    database: string;
    created_at: string;
    residents_count?: number;
}

interface Props {
    tenants: {
        data: Tenant[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
}

export default function Index({ tenants }: Props) {
    const handleDelete = (tenant: Tenant, event: React.MouseEvent) => {
        event.preventDefault();
        if (
            confirm(
                `Are you sure you want to delete "${tenant.name}"? This action cannot be undone.`,
            )
        ) {
            router.delete(admin.tenants.destroy(tenant.id).url);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tenant Management" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-foreground">
                            Tenant Management
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Manage hospitals and their domains
                        </p>
                    </div>
                    <Link href={admin.tenants.create().url}>
                        <Button>Create Tenant</Button>
                    </Link>
                </div>

                <div className="rounded-lg border border-sidebar-border bg-card">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="border-b border-sidebar-border bg-muted/50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-sm font-medium text-foreground">
                                        Name
                                    </th>
                                    <th className="px-4 py-3 text-left text-sm font-medium text-foreground">
                                        Domain
                                    </th>
                                    <th className="px-4 py-3 text-left text-sm font-medium text-foreground">
                                        Database
                                    </th>
                                    <th className="px-4 py-3 text-left text-sm font-medium text-foreground">
                                        Residents
                                    </th>
                                    <th className="px-4 py-3 text-left text-sm font-medium text-foreground">
                                        Created
                                    </th>
                                    <th className="px-4 py-3 text-right text-sm font-medium text-foreground">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-sidebar-border">
                                {tenants.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="px-4 py-8 text-center text-sm text-muted-foreground"
                                        >
                                            No tenants found. Create your first
                                            tenant to get started.
                                        </td>
                                    </tr>
                                ) : (
                                    tenants.data.map((tenant) => (
                                        <tr
                                            key={tenant.id}
                                            className="hover:bg-muted/50"
                                        >
                                            <td className="px-4 py-3 text-sm font-medium text-foreground">
                                                {tenant.name}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-muted-foreground">
                                                <code className="rounded bg-muted px-2 py-1 text-xs">
                                                    {tenant.domain}
                                                </code>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-muted-foreground">
                                                <code className="rounded bg-muted px-2 py-1 text-xs">
                                                    {tenant.database}
                                                </code>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-muted-foreground">
                                                {tenant.residents_count ?? 0}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-muted-foreground">
                                                {new Date(
                                                    tenant.created_at,
                                                ).toLocaleDateString()}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Link
                                                        href={admin.tenants.show(
                                                            tenant.id,
                                                        ).url}
                                                    >
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                        >
                                                            View
                                                        </Button>
                                                    </Link>
                                                    <Link
                                                        href={admin.tenants.edit(
                                                            tenant.id,
                                                        ).url}
                                                    >
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                        >
                                                            Edit
                                                        </Button>
                                                    </Link>
                                                    <Button
                                                        variant="destructive"
                                                        size="sm"
                                                        onClick={(e) =>
                                                            handleDelete(
                                                                tenant,
                                                                e,
                                                            )
                                                        }
                                                    >
                                                        Delete
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {tenants.last_page > 1 && (
                        <div className="border-t border-sidebar-border px-4 py-3">
                            <div className="flex items-center justify-between">
                                <div className="text-sm text-muted-foreground">
                                    Showing {tenants.data.length} of{' '}
                                    {tenants.total} tenants
                                </div>
                                <div className="flex gap-2">
                                    {tenants.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`rounded px-3 py-1 text-sm ${
                                                link.active
                                                    ? 'bg-primary text-primary-foreground'
                                                    : 'bg-muted text-muted-foreground hover:bg-muted/80'
                                            } ${!link.url ? 'pointer-events-none opacity-50' : ''}`}
                                        >
                                            <span
                                                dangerouslySetInnerHTML={{
                                                    __html: link.label,
                                                }}
                                            />
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}

