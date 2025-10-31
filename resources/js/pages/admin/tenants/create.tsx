import admin from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
        title: 'Create',
        href: admin.tenants.create().url,
    },
];

export default function Create() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Tenant" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold text-foreground">
                        Create New Tenant
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Add a new hospital with its domain and database
                        configuration
                    </p>
                </div>

                <Form
                    action={admin.tenants.store().url}
                    method="post"
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="rounded-lg border border-sidebar-border bg-card p-6">
                                <HeadingSmall
                                    title="Tenant Information"
                                    description="Enter the basic information for the new tenant"
                                />

                                <div className="mt-6 space-y-6">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">
                                            Hospital Name
                                        </Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            type="text"
                                            required
                                            autoFocus
                                            placeholder="e.g., City General Hospital"
                                            className="mt-1 block w-full"
                                        />
                                        <InputError
                                            className="mt-2"
                                            message={errors.name}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="domain">Domain</Label>
                                        <Input
                                            id="domain"
                                            name="domain"
                                            type="text"
                                            required
                                            placeholder="e.g., citygeneral.example.com"
                                            className="mt-1 block w-full"
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            Add this domain to Coolify after
                                            creation
                                        </p>
                                        <InputError
                                            className="mt-2"
                                            message={errors.domain}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="database">
                                            Database Name
                                        </Label>
                                        <Input
                                            id="database"
                                            name="database"
                                            type="text"
                                            required
                                            placeholder="e.g., citygeneral (auto-generated if empty)"
                                            className="mt-1 block w-full"
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            Only lowercase letters, numbers, and
                                            underscores. Leave empty to
                                            auto-generate from domain.
                                        </p>
                                        <InputError
                                            className="mt-2"
                                            message={errors.database}
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="flex items-center justify-end gap-4">
                                <Link href={admin.tenants.index().url}>
                                    <Button type="button" variant="outline">
                                        Cancel
                                    </Button>
                                </Link>
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Creating...' : 'Create Tenant'}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}

