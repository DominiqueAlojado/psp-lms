import { useEffect } from 'react';

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
    {
        title: 'View',
        href: '#',
    },
];

interface Tenant {
    id: number;
    name: string;
    domain: string;
    database: string;
    created_at: string;
    updated_at: string;
    residents_count: number;
}

interface LocalSetupStatus {
    hosts_configured: boolean;
    virtual_host_configured: boolean;
}

interface Props {
    tenant: Tenant;
    localSetupStatus: LocalSetupStatus;
    success?: string;
    info?: string;
    setupNeeded?: boolean;
    errors?: {
        setup?: string;
    };
}

export default function Show({
    tenant,
    localSetupStatus,
    success,
    info,
    setupNeeded,
    errors,
}: Props) {
    // Show success/error messages if present
    useEffect(() => {
        if (success) {
            alert(success);
        }
        if (info) {
            alert(info);
        }
        if (errors?.setup) {
            alert(`❌ ${errors.setup}`);
        }
    }, [success, info, errors]);
    const handleSetupLocal = () => {
        if (
            confirm(
                'This will add the domain to your hosts file and create virtual host configuration. Apache will be automatically restarted if possible. You may be prompted for admin access. Continue?',
            )
        ) {
            router.post(
                admin.tenants.setupLocal(tenant.id).url,
                {},
                {
                    preserveScroll: true,
                           onSuccess: () => {
                               // Reload to show updated status
                               router.reload({ only: ['localSetupStatus', 'setupNeeded'] });
                               // Don't show alert - the page will reload with updated status
                           },
                    onError: (errors) => {
                        console.error('Setup errors:', errors);
                        const errorMessage =
                            typeof errors.setup === 'string'
                                ? errors.setup
                                : errors.message ||
                                  Object.values(errors)[0] ||
                                  'Unknown error';
                        alert(`Setup failed: ${errorMessage}`);
                    },
                },
            );
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Tenant: ${tenant.name}`} />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-foreground">
                            {tenant.name}
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Tenant details and information
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Link href={admin.tenants.edit(tenant.id).url}>
                            <Button variant="outline">Edit</Button>
                        </Link>
                        <Link href={admin.tenants.index().url}>
                            <Button variant="outline">Back to List</Button>
                        </Link>
                    </div>
                </div>

                <div className="rounded-lg border border-sidebar-border bg-card p-6">
                    <dl className="space-y-6">
                        <div>
                            <dt className="text-sm font-medium text-muted-foreground">
                                Hospital Name
                            </dt>
                            <dd className="mt-1 text-sm text-foreground">
                                {tenant.name}
                            </dd>
                        </div>

                        <div>
                            <dt className="text-sm font-medium text-muted-foreground">
                                Domain
                            </dt>
                            <dd className="mt-1 text-sm text-foreground">
                                <code className="rounded bg-muted px-2 py-1 text-xs">
                                    {tenant.domain}
                                </code>
                            </dd>
                            <p className="mt-2 text-xs text-muted-foreground">
                                Add this domain to Coolify if not already
                                configured
                            </p>
                        </div>

                        <div>
                            <dt className="text-sm font-medium text-muted-foreground">
                                Database Name
                            </dt>
                            <dd className="mt-1 text-sm text-foreground">
                                <code className="rounded bg-muted px-2 py-1 text-xs">
                                    {tenant.database}
                                </code>
                            </dd>
                        </div>

                        <div>
                            <dt className="text-sm font-medium text-muted-foreground">
                                Residents
                            </dt>
                            <dd className="mt-1 text-sm text-foreground">
                                {tenant.residents_count}
                            </dd>
                        </div>

                        <div>
                            <dt className="text-sm font-medium text-muted-foreground">
                                Created At
                            </dt>
                            <dd className="mt-1 text-sm text-foreground">
                                {new Date(tenant.created_at).toLocaleString()}
                            </dd>
                        </div>

                        <div>
                            <dt className="text-sm font-medium text-muted-foreground">
                                Last Updated
                            </dt>
                            <dd className="mt-1 text-sm text-foreground">
                                {new Date(tenant.updated_at).toLocaleString()}
                            </dd>
                        </div>
                    </dl>
                </div>

                {/* Local Development Settings */}
                <div className="rounded-lg border border-sidebar-border bg-card p-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h3 className="text-lg font-semibold text-foreground">
                                Local Development Settings
                            </h3>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Configure this tenant for local development on
                                Windows with Laragon
                            </p>
                        </div>
                        <Button
                            onClick={handleSetupLocal}
                            disabled={
                                localSetupStatus.hosts_configured &&
                                localSetupStatus.virtual_host_configured
                            }
                        >
                            Setup Local Environment
                        </Button>
                    </div>

                    <div className="mt-6 space-y-4">
                        <div className="flex items-center justify-between rounded-lg border border-sidebar-border bg-muted/50 p-4">
                            <div className="flex items-center gap-3">
                                <div
                                    className={`h-3 w-3 rounded-full ${localSetupStatus.hosts_configured ? 'bg-green-500' : 'bg-gray-400'}`}
                                />
                                <div>
                                    <dt className="text-sm font-medium text-foreground">
                                        Hosts File
                                    </dt>
                                    <dd className="mt-1 text-xs text-muted-foreground">
                                        Domain entry in Windows hosts file
                                    </dd>
                                </div>
                            </div>
                            <span className="text-sm font-medium">
                                {localSetupStatus.hosts_configured
                                    ? 'Configured'
                                    : 'Not configured'}
                            </span>
                        </div>

                        <div className="flex items-center justify-between rounded-lg border border-sidebar-border bg-muted/50 p-4">
                            <div className="flex items-center gap-3">
                                <div
                                    className={`h-3 w-3 rounded-full ${localSetupStatus.virtual_host_configured ? 'bg-green-500' : 'bg-gray-400'}`}
                                />
                                <div>
                                    <dt className="text-sm font-medium text-foreground">
                                        Virtual Host
                                    </dt>
                                    <dd className="mt-1 text-xs text-muted-foreground">
                                        Laragon Apache virtual host
                                        configuration
                                    </dd>
                                </div>
                            </div>
                            <span className="text-sm font-medium">
                                {localSetupStatus.virtual_host_configured
                                    ? 'Configured'
                                    : 'Not configured'}
                            </span>
                        </div>
                    </div>

                    {localSetupStatus.hosts_configured &&
                    localSetupStatus.virtual_host_configured ? (
                        <div className="mt-4 rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-900 dark:bg-green-950">
                            <p className="text-sm text-green-800 dark:text-green-200">
                                ✅ Local environment is configured. Restart
                                Apache in Laragon if you just ran setup, then
                                visit:{' '}
                                <a
                                    href={`http://${tenant.domain}/`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="font-medium underline"
                                >
                                    http://{tenant.domain}/
                                </a>
                            </p>
                        </div>
                    ) : (
                        <div className="mt-4 rounded-lg border border-yellow-200 bg-yellow-50 p-3 dark:border-yellow-900 dark:bg-yellow-950">
                            <p className="text-sm text-yellow-800 dark:text-yellow-200">
                                ⚠️ Click "Setup Local Environment" to
                                automatically configure hosts file and virtual
                                host. You may be prompted for administrator
                                access.
                            </p>
                        </div>
                    )}
                </div>

                {/* Coolify Configuration */}
                <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950">
                    <h3 className="text-sm font-semibold text-blue-900 dark:text-blue-100">
                        Production (Coolify) Configuration
                    </h3>
                    <p className="mt-2 text-sm text-blue-800 dark:text-blue-200">
                        To deploy this tenant to production, add the domain{' '}
                        <code className="rounded bg-blue-100 px-1 py-0.5 text-xs dark:bg-blue-900">
                            {tenant.domain}
                        </code>{' '}
                        to your Coolify application. Coolify will automatically
                        handle DNS, SSL certificates, and routing.
                    </p>
                </div>
            </div>
        </AppLayout>
    );
}

