import HeadingSmall from '@/components/heading-small';
import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Cog, Eye, FolderKanban } from 'lucide-react';
import { useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'System Configuration',
        href: '/settings/configurations',
    },
];

interface ConfigurationItem {
    id: number;
    key: string;
    module: string;
    label: string;
    description: string | null;
    type: 'boolean' | 'string' | 'text' | 'integer' | 'number' | 'json';
    value: boolean | string | number | Record<string, unknown> | null;
    is_public: boolean;
    is_editable: boolean;
    sort_order: number;
}

interface Props {
    configurations: ConfigurationItem[];
    summary: {
        total: number;
        public: number;
        modules: number;
    };
}

export default function Configurations() {
    const { configurations, summary } = usePage<Props>().props;
    const [savingToggleKey, setSavingToggleKey] = useState<string | null>(null);

    const groupedConfigurations = useMemo(() => {
        return configurations.reduce<Record<string, ConfigurationItem[]>>(
            (groups, configuration) => {
                if (!groups[configuration.module]) {
                    groups[configuration.module] = [];
                }

                groups[configuration.module].push(configuration);

                return groups;
            },
            {},
        );
    }, [configurations]);

    const hasManualConfigs = useMemo(
        () =>
            configurations.some(
                (configuration) =>
                    configuration.is_editable &&
                    configuration.type !== 'boolean',
            ),
        [configurations],
    );

    const { data, setData, patch, processing, isDirty, errors } = useForm<{
        configs: Record<string, unknown>;
    }>({
        configs: Object.fromEntries(
            configurations.map((configuration) => [
                configuration.key,
                configuration.value,
            ]),
        ),
    });

    const updateConfigValue = (key: string, value: unknown) => {
        setData('configs', {
            ...data.configs,
            [key]: value,
        });
    };

    const submitSingleConfig = (key: string, value: unknown) => {
        setSavingToggleKey(key);

        patch('/settings/configurations', {
            data: {
                configs: {
                    [key]: value,
                },
            },
            preserveScroll: true,
            preserveState: true,
            onFinish: () => setSavingToggleKey(null),
        });
    };

    const submit = () => {
        patch('/settings/configurations', {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="System Configuration" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="System Configuration"
                        description="Control public-facing notices and future platform toggles from one place."
                    />

                    <div className="grid gap-4 md:grid-cols-3">
                        <StatCard
                            title="Configs"
                            value={summary.total}
                            description="Editable platform settings currently registered"
                            icon={Cog}
                            iconColor="text-primary"
                        />
                        <StatCard
                            title="Public Flags"
                            value={summary.public}
                            description="Settings exposed to the frontend experience"
                            icon={Eye}
                            iconColor="text-primary"
                        />
                        <StatCard
                            title="Modules"
                            value={summary.modules}
                            description="Config groups ready to scale as more toggles are added"
                            icon={FolderKanban}
                            iconColor="text-primary"
                        />
                    </div>

                    {Object.entries(groupedConfigurations).map(
                        ([moduleName, moduleConfigurations]) => (
                            <Card
                                key={moduleName}
                                className="overflow-hidden border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))]"
                            >
                                <CardHeader className="pb-3">
                                    <CardTitle>{moduleName}</CardTitle>
                                    <CardDescription>
                                        Configuration values in this module can
                                        be expanded over time without changing
                                        the settings flow.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {moduleConfigurations.map(
                                        (configuration) => {
                                            const currentValue = Boolean(
                                                data.configs[configuration.key],
                                            );

                                            return (
                                                <div
                                                    key={configuration.key}
                                                    className="rounded-2xl border border-border/70 bg-background/80 p-4"
                                                >
                                                    <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                                        <div className="space-y-2">
                                                            <div className="flex flex-wrap items-center gap-2">
                                                                <Label className="text-sm font-semibold text-foreground">
                                                                    {
                                                                        configuration.label
                                                                    }
                                                                </Label>
                                                                {configuration.is_public && (
                                                                    <Badge variant="secondary">
                                                                        Public
                                                                    </Badge>
                                                                )}
                                                                <Badge
                                                                    variant="outline"
                                                                >
                                                                    {
                                                                        configuration.type
                                                                    }
                                                                </Badge>
                                                            </div>
                                                            <p className="text-sm leading-6 text-muted-foreground">
                                                                {configuration.description ??
                                                                    'No description provided.'}
                                                            </p>
                                                            <p className="font-mono text-xs text-muted-foreground/80">
                                                                {
                                                                    configuration.key
                                                                }
                                                            </p>
                                                        </div>

                                                        <div className="w-full lg:max-w-sm">
                                                            {configuration.type ===
                                                            'boolean' ? (
                                                                <div className="flex items-center justify-between rounded-xl border border-border/70 bg-card/70 px-4 py-3">
                                                                    <div className="space-y-1">
                                                                        <span className="text-sm font-medium text-foreground">
                                                                            {currentValue
                                                                                ? 'Enabled'
                                                                                : 'Disabled'}
                                                                        </span>
                                                                        <p className="text-xs text-muted-foreground">
                                                                            Toggle
                                                                            this
                                                                            setting
                                                                            on
                                                                            or
                                                                            off.
                                                                        </p>
                                                                    </div>
                                                                    <button
                                                                        type="button"
                                                                        role="switch"
                                                                        aria-checked={
                                                                            currentValue
                                                                        }
                                                                        disabled={
                                                                            !configuration.is_editable
                                                                            || savingToggleKey ===
                                                                                configuration.key
                                                                        }
                                                                        onClick={() =>
                                                                            {
                                                                                const nextValue =
                                                                                    !currentValue;

                                                                                updateConfigValue(
                                                                                    configuration.key,
                                                                                    nextValue,
                                                                                );

                                                                                submitSingleConfig(
                                                                                    configuration.key,
                                                                                    nextValue,
                                                                                );
                                                                            }
                                                                        }
                                                                        className={cn(
                                                                            'relative inline-flex h-7 w-12 shrink-0 items-center rounded-full border transition-colors',
                                                                            'focus-visible:border-primary/35 focus-visible:ring-ring/35 focus-visible:outline-none focus-visible:ring-[3px]',
                                                                            !configuration.is_editable &&
                                                                                'cursor-not-allowed opacity-60',
                                                                            currentValue
                                                                                ? 'border-primary/60 bg-primary/85'
                                                                                : 'border-border bg-muted',
                                                                        )}
                                                                    >
                                                                        <span
                                                                            className={cn(
                                                                                'inline-block h-5 w-5 rounded-full bg-white shadow-sm transition-transform',
                                                                                currentValue
                                                                                    ? 'translate-x-6'
                                                                                    : 'translate-x-1',
                                                                            )}
                                                                        />
                                                                    </button>
                                                                </div>
                                                            ) : configuration.type ===
                                                                  'text' ? (
                                                                <Textarea
                                                                    value={String(
                                                                        data
                                                                            .configs[
                                                                            configuration
                                                                                .key
                                                                        ] ??
                                                                            '',
                                                                    )}
                                                                    disabled={
                                                                        !configuration.is_editable
                                                                    }
                                                                    onChange={(
                                                                        event,
                                                                    ) =>
                                                                        updateConfigValue(
                                                                            configuration.key,
                                                                            event
                                                                                .target
                                                                                .value,
                                                                        )
                                                                    }
                                                                />
                                                            ) : (
                                                                <Input
                                                                    type={
                                                                        configuration.type ===
                                                                            'integer' ||
                                                                        configuration.type ===
                                                                            'number'
                                                                            ? 'number'
                                                                            : 'text'
                                                                    }
                                                                    value={String(
                                                                        data
                                                                            .configs[
                                                                            configuration
                                                                                .key
                                                                        ] ??
                                                                            '',
                                                                    )}
                                                                    disabled={
                                                                        !configuration.is_editable
                                                                    }
                                                                    onChange={(
                                                                        event,
                                                                    ) =>
                                                                        updateConfigValue(
                                                                            configuration.key,
                                                                            event
                                                                                .target
                                                                                .value,
                                                                        )
                                                                    }
                                                                />
                                                            )}
                                                            {errors[
                                                                `configs.${configuration.key}` as keyof typeof errors
                                                            ] && (
                                                                <p className="mt-2 text-sm text-destructive">
                                                                    {
                                                                        errors[
                                                                            `configs.${configuration.key}` as keyof typeof errors
                                                                        ]
                                                                    }
                                                                </p>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                            );
                                        },
                                    )}
                                </CardContent>
                            </Card>
                        ),
                    )}

                    {hasManualConfigs && (
                        <div className="flex justify-end">
                            <Button
                                type="button"
                                disabled={processing || !isDirty}
                                onClick={submit}
                            >
                                Save Changes
                            </Button>
                        </div>
                    )}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
