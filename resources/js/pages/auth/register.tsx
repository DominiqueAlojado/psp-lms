import { login } from '@/routes';
import { store } from '@/routes/register';
import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import { Head, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { z } from 'zod';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';

type OrganizationOption = {
    id: number;
    name: string;
};

type RegisterPageProps = InertiaPageProps & {
    organizations: OrganizationOption[];
    yearLevels: string[];
};

export default function Register() {
    const { organizations = [], yearLevels = [] } =
        usePage<RegisterPageProps>().props;
    const schema = useMemo(
        () =>
            z
                .object({
                    first_name: z.string().min(1, 'First name is required'),
                    middle_name: z
                        .string()
                        .max(255)
                        .optional()
                        .or(z.literal('')),
                    last_name: z.string().min(1, 'Last name is required'),
                    email: z
                        .string()
                        .min(1, 'Email is required')
                        .email('Please enter a valid email'),
                    organization_id: z
                        .string()
                        .min(1, 'Institution is required'),
                    contact_number: z
                        .string()
                        .min(1, 'Contact number is required')
                        .regex(
                            /^(\+63|0)?9\d{9}$/,
                            'Use a valid Philippine mobile number (09123456789 or +639123456789)',
                        ),
                    course: z.string().min(1, 'Course is required'),
                    year_level: z
                        .string()
                        .min(1, 'Year level is required')
                        .refine(
                            (value) =>
                                yearLevels.length === 0 ||
                                yearLevels.includes(value),
                            { message: 'Select a valid year level' },
                        ),
                    password: z
                        .string()
                        .min(8, 'Password must be at least 8 characters'),
                    password_confirmation: z
                        .string()
                        .min(1, 'Please confirm your password'),
                })
                .refine(
                    (values) =>
                        values.password === values.password_confirmation,
                    {
                        message: "Passwords don't match",
                        path: ['password_confirmation'],
                    },
                ),
        [yearLevels],
    );

    const { data, setData, post, processing, errors, reset } = useForm({
        first_name: '',
        middle_name: '',
        last_name: '',
        email: '',
        organization_id: '',
        contact_number: '',
        course: '',
        year_level: '',
        password: '',
        password_confirmation: '',
    });

    const [clientErrors, setClientErrors] = useState<Record<string, string>>(
        {},
    );

    const combinedError = (field: keyof typeof data) =>
        errors[field] || clientErrors[field] || '';

    const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const parsed = schema.safeParse(data);

        if (!parsed.success) {
            const fieldErrors = parsed.error.flatten().fieldErrors;
            const mapped: Record<string, string> = {};
            Object.entries(fieldErrors).forEach(([key, messages]) => {
                if (messages && messages.length > 0) {
                    mapped[key] = messages[0];
                }
            });
            setClientErrors(mapped);
            return;
        }

        setClientErrors({});

        post(store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                reset('password', 'password_confirmation');
            },
        });
    };

    return (
        <AuthLayout
            title="Create an account"
            description="Enter your details below to create your account"
        >
            <Head title="Register" />
            <form
                onSubmit={handleSubmit}
                className="flex flex-col gap-6"
                noValidate
            >
                <div className="grid gap-6">
                    <div className="grid gap-2">
                        <Label htmlFor="first_name">First name</Label>
                        <Input
                            id="first_name"
                            type="text"
                            autoFocus
                            tabIndex={1}
                            autoComplete="given-name"
                            name="first_name"
                            placeholder="First name"
                            value={data.first_name}
                            onChange={(event) =>
                                setData('first_name', event.target.value)
                            }
                        />
                        <InputError
                            message={combinedError('first_name')}
                            className="mt-2"
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="middle_name">
                            Middle name{' '}
                            <span className="text-muted-foreground">
                                (optional)
                            </span>
                        </Label>
                        <Input
                            id="middle_name"
                            type="text"
                            tabIndex={2}
                            autoComplete="additional-name"
                            name="middle_name"
                            placeholder="Middle name"
                            value={data.middle_name ?? ''}
                            onChange={(event) =>
                                setData('middle_name', event.target.value)
                            }
                        />
                        <InputError message={combinedError('middle_name')} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="last_name">Last name</Label>
                        <Input
                            id="last_name"
                            type="text"
                            tabIndex={3}
                            autoComplete="family-name"
                            name="last_name"
                            placeholder="Last name"
                            value={data.last_name}
                            onChange={(event) =>
                                setData('last_name', event.target.value)
                            }
                        />
                        <InputError
                            message={combinedError('last_name')}
                            className="mt-2"
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            tabIndex={4}
                            autoComplete="email"
                            name="email"
                            placeholder="email@example.com"
                            value={data.email}
                            onChange={(event) =>
                                setData('email', event.target.value)
                            }
                        />
                        <InputError message={combinedError('email')} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="organization_id">Institution</Label>
                        <select
                            id="organization_id"
                            name="organization_id"
                            tabIndex={5}
                            value={data.organization_id}
                            onChange={(event) =>
                                setData('organization_id', event.target.value)
                            }
                            className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            <option value="">Select institution</option>
                            {organizations.map(
                                (organization: OrganizationOption) => (
                                    <option
                                        key={organization.id}
                                        value={String(organization.id)}
                                    >
                                        {organization.name}
                                    </option>
                                ),
                            )}
                        </select>
                        <InputError
                            message={combinedError('organization_id')}
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="contact_number">Contact number</Label>
                        <Input
                            id="contact_number"
                            name="contact_number"
                            type="tel"
                            autoComplete="tel-national"
                            placeholder="09123456789 or +639123456789"
                            tabIndex={6}
                            value={data.contact_number}
                            onChange={(event) =>
                                setData(
                                    'contact_number',
                                    event.target.value.replace(/[^\d+]/g, ''),
                                )
                            }
                        />
                        <InputError message={combinedError('contact_number')} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="course">Course</Label>
                        <Input
                            id="course"
                            name="course"
                            placeholder="Course"
                            tabIndex={7}
                            value={data.course}
                            onChange={(event) =>
                                setData('course', event.target.value)
                            }
                        />
                        <InputError message={combinedError('course')} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="year_level">Year level</Label>
                        <select
                            id="year_level"
                            name="year_level"
                            tabIndex={8}
                            value={data.year_level}
                            onChange={(event) =>
                                setData('year_level', event.target.value)
                            }
                            className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            <option value="">Select year level</option>
                            {yearLevels.map((level: string) => (
                                <option key={level} value={level}>
                                    {level}
                                </option>
                            ))}
                        </select>
                        <InputError message={combinedError('year_level')} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password">Password</Label>
                        <Input
                            id="password"
                            type="password"
                            tabIndex={9}
                            autoComplete="new-password"
                            name="password"
                            placeholder="Password"
                            value={data.password}
                            onChange={(event) =>
                                setData('password', event.target.value)
                            }
                        />
                        <InputError message={combinedError('password')} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password_confirmation">
                            Confirm password
                        </Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            tabIndex={10}
                            autoComplete="new-password"
                            name="password_confirmation"
                            placeholder="Confirm password"
                            value={data.password_confirmation}
                            onChange={(event) =>
                                setData(
                                    'password_confirmation',
                                    event.target.value,
                                )
                            }
                        />
                        <InputError
                            message={combinedError('password_confirmation')}
                        />
                    </div>

                    <Button
                        type="submit"
                        className="mt-2 w-full"
                        tabIndex={11}
                        data-test="register-user-button"
                        disabled={processing}
                    >
                        {processing && <Spinner />}
                        Create account
                    </Button>
                </div>

                <div className="text-center text-sm text-muted-foreground">
                    Already have an account?{' '}
                    <TextLink href={login().url} tabIndex={12}>
                        Log in
                    </TextLink>
                </div>
            </form>
        </AuthLayout>
    );
}
