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
        title: 'Edit',
        href: '#',
    },
];

interface Tenant {
    id: number;
    name: string;
    domain: string;
    database: string;
}

interface Props {
    tenant: Tenant;
}

export default function Edit({ tenant }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Tenant" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold text-foreground">
                        Edit Tenant
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Update tenant information
                    </p>
                </div>

                <Form
                    action={admin.tenants.update(tenant.id).url}
                    method="put"
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="rounded-lg border border-sidebar-border bg-card p-6">
                                <HeadingSmall
                                    title="Tenant Information"
                                    description="Update the tenant information"
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
                                            defaultValue={tenant.name}
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
                                            defaultValue={tenant.domain}
                                            className="mt-1 block w-full"
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            Update this domain in Coolify if
                                            changed
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
                                            defaultValue={tenant.database}
                                            className="mt-1 block w-full"
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            Only lowercase letters, numbers, and
                                            underscores.
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
                                    {processing ? 'Updating...' : 'Update Tenant'}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}

