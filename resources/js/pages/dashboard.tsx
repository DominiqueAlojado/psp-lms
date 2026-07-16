import HeadingSmall from '@/components/heading-small';
import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    BookOpen,
    CalendarDays,
    CheckCircle2,
    Clock3,
    FileText,
    Sparkles,
    Users,
} from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const activityItems = [
    {
        title: 'Question bank review queue',
        description: '12 questions are waiting for approval and quality review.',
        tone: 'warning',
    },
    {
        title: 'Upcoming institutional exam',
        description: 'Internal Medicine Mock Exam opens tomorrow at 8:00 AM.',
        tone: 'default',
    },
    {
        title: 'Resident activity is healthy',
        description: 'Most recent cohort completion rate is ahead of last month.',
        tone: 'success',
    },
];

const quickActions = [
    {
        title: 'Create assessment',
        description: 'Start a new exam draft with sections, timing, and scoring.',
        icon: FileText,
    },
    {
        title: 'Review question bank',
        description: 'Approve, edit, and organize reusable questions.',
        icon: BookOpen,
    },
    {
        title: 'Open analytics',
        description: 'Check pass rates, question performance, and cohort trends.',
        icon: BarChart3,
    },
];

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto px-5 py-6 md:px-6">
                <Card className="overflow-hidden border-primary/12 bg-[linear-gradient(135deg,color-mix(in_oklab,var(--color-accent)_88%,white)_0%,color-mix(in_oklab,var(--color-card)_96%,var(--color-accent))_100%)] dark:bg-[linear-gradient(135deg,color-mix(in_oklab,var(--color-accent)_72%,black)_0%,color-mix(in_oklab,var(--color-card)_92%,var(--color-accent))_100%)]">
                    <CardContent className="grid gap-8 p-7 lg:grid-cols-[1.4fr_0.9fr] lg:items-center">
                        <div className="space-y-5">
                            <Badge className="gap-1.5">
                                <Sparkles className="size-3" />
                                Platform overview
                            </Badge>
                            <HeadingSmall
                                title="A calmer command center for exams, residents, and learning operations."
                                description="Track delivery health, surface the highest-priority actions, and keep the entire learning workflow moving from one place."
                                className="max-w-3xl"
                            />
                            <div className="flex flex-wrap gap-3">
                                <Button>
                                    View analytics
                                    <ArrowRight className="h-4 w-4" />
                                </Button>
                                <Button variant="outline">Open question bank</Button>
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <Card className="border-primary/12 bg-background/90">
                                <CardContent className="space-y-2 p-5">
                                    <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                        Delivery status
                                    </p>
                                    <div className="flex items-end gap-2">
                                        <span className="text-4xl font-semibold tracking-[-0.06em] text-foreground">
                                            94%
                                        </span>
                                        <span className="pb-1 text-sm text-muted-foreground">
                                            operational health
                                        </span>
                                    </div>
                                    <p className="text-sm leading-6 text-muted-foreground">
                                        Exams, grading, and content workflows are running smoothly.
                                    </p>
                                </CardContent>
                            </Card>

                            <Card className="border-primary/12 bg-background/90">
                                <CardContent className="space-y-2 p-5">
                                    <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                        This week
                                    </p>
                                    <div className="flex items-end gap-2">
                                        <span className="text-4xl font-semibold tracking-[-0.06em] text-foreground">
                                            18
                                        </span>
                                        <span className="pb-1 text-sm text-muted-foreground">
                                            active workflows
                                        </span>
                                    </div>
                                    <p className="text-sm leading-6 text-muted-foreground">
                                        Publishing, analytics review, resident tracking, and exam scheduling.
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-4 xl:grid-cols-4">
                    <StatCard
                        title="Residents monitored"
                        value="248"
                        description="Across all currently active organizations"
                        icon={Users}
                        iconColor="text-primary"
                        trend="+8% month over month"
                        trendDirection="up"
                    />
                    <StatCard
                        title="Assessments this month"
                        value="36"
                        description="Published, draft, and scheduled exams"
                        icon={FileText}
                        iconColor="text-primary"
                        trend="+5 net new"
                        trendDirection="up"
                    />
                    <StatCard
                        title="Completion rate"
                        value="91%"
                        description="Average exam completion across active cohorts"
                        icon={CheckCircle2}
                        iconColor="text-primary"
                        trend="+3.2% from last cycle"
                        trendDirection="up"
                    />
                    <StatCard
                        title="Upcoming events"
                        value="7"
                        description="Courses, meetings, and exam-related milestones"
                        icon={CalendarDays}
                        iconColor="text-primary"
                        trend="Next in 14 hours"
                        trendDirection="neutral"
                    />
                </div>

                <div className="grid gap-6 lg:grid-cols-[1.5fr_0.9fr]">
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle>Operations snapshot</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <div className="grid gap-4 md:grid-cols-3">
                                <div className="rounded-2xl border border-border/70 bg-background/80 p-4">
                                    <div className="mb-2 flex items-center gap-2 text-sm font-medium text-foreground">
                                        <Clock3 className="h-4 w-4 text-primary" />
                                        Review queue
                                    </div>
                                    <p className="text-3xl font-semibold tracking-[-0.05em] text-foreground">
                                        12
                                    </p>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        Pending approvals across question bank and content workflows.
                                    </p>
                                </div>
                                <div className="rounded-2xl border border-border/70 bg-background/80 p-4">
                                    <div className="mb-2 flex items-center gap-2 text-sm font-medium text-foreground">
                                        <BarChart3 className="h-4 w-4 text-primary" />
                                        Analytics checks
                                    </div>
                                    <p className="text-3xl font-semibold tracking-[-0.05em] text-foreground">
                                        5
                                    </p>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        Exams recommended for closer item analysis this week.
                                    </p>
                                </div>
                                <div className="rounded-2xl border border-border/70 bg-background/80 p-4">
                                    <div className="mb-2 flex items-center gap-2 text-sm font-medium text-foreground">
                                        <CalendarDays className="h-4 w-4 text-primary" />
                                        Scheduled today
                                    </div>
                                    <p className="text-3xl font-semibold tracking-[-0.05em] text-foreground">
                                        4
                                    </p>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        Assessments and events queued to start before end of day.
                                    </p>
                                </div>
                            </div>

                            <div className="rounded-[1.5rem] border border-dashed border-border/80 bg-muted/30 p-5">
                                <div className="mb-3 flex items-center justify-between gap-3">
                                    <div>
                                        <p className="text-sm font-semibold text-foreground">
                                            Performance trend preview
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            A live chart module can sit here once the reporting view is connected.
                                        </p>
                                    </div>
                                    <Badge variant="outline">Preview layout</Badge>
                                </div>
                                <div className="grid gap-3 sm:grid-cols-6">
                                    {[42, 58, 54, 71, 66, 79].map((value, index) => (
                                        <div
                                            key={index}
                                            className="flex h-40 flex-col justify-end rounded-2xl bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-accent)_34%,transparent),color-mix(in_oklab,var(--color-card)_96%,white))] p-3 dark:bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-accent)_40%,transparent),color-mix(in_oklab,var(--color-card)_90%,var(--color-accent)))]"
                                        >
                                            <div
                                                className="rounded-xl bg-[image:var(--gradient-brand)]"
                                                style={{ height: `${value}%` }}
                                            />
                                            <span className="mt-3 text-xs font-medium text-muted-foreground">
                                                W{index + 1}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle>Priority activity</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {activityItems.map((item) => (
                                    <div
                                        key={item.title}
                                        className="rounded-2xl border border-border/70 bg-background/80 p-4"
                                    >
                                        <div className="mb-2 flex items-center gap-2">
                                            <Badge
                                                variant={
                                                    item.tone === 'warning'
                                                        ? 'outline'
                                                        : item.tone === 'success'
                                                          ? 'default'
                                                          : 'secondary'
                                                }
                                            >
                                                {item.tone === 'warning'
                                                    ? 'Needs review'
                                                    : item.tone === 'success'
                                                      ? 'Healthy'
                                                      : 'Upcoming'}
                                            </Badge>
                                        </div>
                                        <p className="text-sm font-semibold text-foreground">
                                            {item.title}
                                        </p>
                                        <p className="mt-1 text-sm leading-6 text-muted-foreground">
                                            {item.description}
                                        </p>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle>Quick actions</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {quickActions.map((action) => (
                                    <button
                                        key={action.title}
                                        type="button"
                                        className="group flex w-full items-start gap-3 rounded-2xl border border-border/70 bg-background/80 p-4 text-left transition-[border-color,transform,box-shadow] hover:-translate-y-0.5 hover:border-primary/15 hover:shadow-[0_20px_44px_-34px_rgb(96_44_193_/_0.35)]"
                                    >
                                        <div className="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-accent text-primary">
                                            <action.icon className="h-4 w-4" />
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <p className="text-sm font-semibold text-foreground">
                                                {action.title}
                                            </p>
                                            <p className="mt-1 text-sm leading-6 text-muted-foreground">
                                                {action.description}
                                            </p>
                                        </div>
                                        <ArrowRight className="mt-1 h-4 w-4 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5 group-hover:text-primary" />
                                    </button>
                                ))}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
