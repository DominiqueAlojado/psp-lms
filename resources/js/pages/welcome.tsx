import { dashboard, login, register } from '@/routes';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    BookOpenCheck,
    ClipboardList,
    GraduationCap,
    ShieldCheck,
    Users,
} from 'lucide-react';

const experiences = [
    {
        title: 'Institution Exams',
        description:
            'Coordinate categories, randomize questions, and publish analytics for every organization.',
        icon: ClipboardList,
    },
    {
        title: 'Complete LMS',
        description:
            'Assignments, learning resources, and resident progress in one consistent workspace.',
        icon: BookOpenCheck,
    },
    {
        title: 'In-Service Readiness',
        description:
            'Benchmark residents against national standards and surface readiness gaps instantly.',
        icon: ShieldCheck,
    },
];

const stats = [
    { label: 'Institution exams delivered', value: '120+' },
    { label: 'Residents onboarded', value: '6K+' },
    { label: 'In-service attempts tracked', value: '18K+' },
];

const pillars = [
    {
        title: 'Assessment Intelligence',
        description:
            'Build rich exams from the question bank, mix long-form and MCQ, and trust the pairing between answers and prompts.',
    },
    {
        title: 'Resident Journey',
        description:
            'A single timeline for onboarding, assignments, grades, and certification keeps everyone aligned.',
    },
    {
        title: 'Institutional Control',
        description:
            'Granular permissions ensure Residents pages are visible only to System Admins and Admins, while staff focus on their modules.',
    },
];

const workflow = [
    {
        title: 'Design',
        description:
            'Blueprint institution exams, competency checks, and in-service drills.',
    },
    {
        title: 'Deliver',
        description:
            'Launch timed exams, assignments, and invigilated sessions with one click.',
    },
    {
        title: 'Evaluate',
        description:
            'Auto-grade where possible, publish feedback, and compare cohorts.',
    },
    {
        title: 'Elevate',
        description:
            'Turn analytics into readiness plans and accreditation-ready reports.',
    },
];

export default function Welcome({
    canRegister = true,
}: {
    canRegister?: boolean;
}) {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="Welcome">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600"
                    rel="stylesheet"
                />
            </Head>

            <div className="min-h-screen bg-background text-foreground">
                <header className="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-6">
                    <div className="text-sm font-semibold tracking-[0.3em] text-muted-foreground uppercase">
                        UNIFIED-LMS
                    </div>
                    <nav className="flex items-center gap-3 text-sm">
                        {auth.user ? (
                            <Link
                                href={dashboard()}
                                className="rounded-full border border-border px-4 py-2 font-medium transition hover:bg-muted"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={login()}
                                    className="rounded-full px-4 py-2 font-medium text-muted-foreground transition hover:text-foreground"
                                >
                                    Log in
                                </Link>
                                {canRegister && (
                                    <Link
                                        href={register()}
                                        className="rounded-full bg-foreground px-4 py-2 font-medium text-background transition hover:opacity-90"
                                    >
                                        Create account
                                    </Link>
                                )}
                            </>
                        )}
                    </nav>
                </header>

                <main className="mx-auto flex w-full max-w-6xl flex-col gap-12 px-6 pb-16">
                    <section className="grid gap-8 rounded-3xl border border-border bg-card/80 p-8 shadow-sm backdrop-blur lg:grid-cols-2">
                        <div className="space-y-6">
                            <p className="inline-flex items-center gap-2 rounded-full bg-primary/10 px-4 py-1 text-xs font-semibold tracking-[0.35em] text-primary uppercase">
                                Comprehensive Residency Platform
                            </p>
                            <div>
                                <h1 className="text-4xl leading-tight font-semibold">
                                    Assess, teach, and certify every resident in
                                    one workspace.
                                </h1>
                                <p className="mt-4 text-lg text-muted-foreground">
                                    UNIFIED-LMS unifies institution exams, daily LMS
                                    operations, and in-service readiness
                                    tracking. Coordinate national cohorts,
                                    manage assignments, and prove competency
                                    with defensible data.
                                </p>
                            </div>
                            <div className="flex flex-wrap items-center gap-3">
                                <Link
                                    href={auth.user ? dashboard() : login()}
                                    className="inline-flex items-center gap-2 rounded-full bg-foreground px-5 py-3 font-medium text-background transition hover:opacity-90"
                                >
                                    {auth.user
                                        ? 'Go to dashboard'
                                        : 'Launch platform'}
                                    <ArrowRight className="h-4 w-4" />
                                </Link>
                                {canRegister && !auth.user && (
                                    <Link
                                        href={register()}
                                        className="inline-flex items-center gap-2 rounded-full border border-border px-5 py-3 font-medium transition hover:bg-muted"
                                    >
                                        Book a walkthrough
                                    </Link>
                                )}
                            </div>
                            <dl className="grid grid-cols-2 gap-5 sm:grid-cols-3">
                                {stats.map((stat) => (
                                    <div key={stat.label}>
                                        <dt className="text-xs tracking-[0.35em] text-muted-foreground uppercase">
                                            {stat.label}
                                        </dt>
                                        <dd className="mt-2 text-2xl font-semibold">
                                            {stat.value}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                        </div>
                        <div className="space-y-4 rounded-3xl border border-border bg-muted/40 p-6">
                            <p className="text-sm font-semibold tracking-[0.35em] text-muted-foreground uppercase">
                                Platform Modes
                            </p>
                            <div className="space-y-4">
                                {experiences.map((experience) => (
                                    <div
                                        key={experience.title}
                                        className="flex items-start gap-4 rounded-2xl border border-border bg-background p-4 shadow-sm"
                                    >
                                        <experience.icon className="h-10 w-10 rounded-xl bg-primary/10 p-2 text-primary" />
                                        <div>
                                            <p className="text-base font-semibold">
                                                {experience.title}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {experience.description}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <div className="grid gap-4 rounded-2xl border border-border bg-background p-4 text-sm text-muted-foreground">
                                <div className="flex items-center gap-2">
                                    <BarChart3 className="h-4 w-4 text-primary" />
                                    Readiness pulseboards built-in
                                </div>
                                <div className="flex items-center gap-2">
                                    <ShieldCheck className="h-4 w-4 text-emerald-500" />
                                    Secure workflows for critical exams
                                </div>
                                <div className="flex items-center gap-2">
                                    <Users className="h-4 w-4 text-sky-500" />
                                    Cohort benchmarking across institutions
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="grid gap-6 rounded-2xl border border-border bg-card/70 p-6 shadow-sm backdrop-blur lg:grid-cols-3">
                        {pillars.map((pillar) => (
                            <div key={pillar.title} className="space-y-3">
                                <h3 className="text-lg font-semibold">
                                    {pillar.title}
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    {pillar.description}
                                </p>
                            </div>
                        ))}
                    </section>

                    <section className="rounded-3xl border border-border bg-[linear-gradient(135deg,color-mix(in_oklab,var(--color-card)_98%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))] p-8 text-foreground shadow-[0_24px_60px_-36px_rgb(35_24_74_/_0.18)] dark:bg-[linear-gradient(135deg,color-mix(in_oklab,var(--color-card)_96%,var(--color-primary)_4%),color-mix(in_oklab,var(--color-card)_92%,black))] dark:shadow-[0_24px_60px_-36px_rgb(0_0_0_/_0.5)]">
                        <div className="flex flex-col gap-8 lg:flex-row lg:justify-between">
                            <div className="space-y-3">
                                <p className="text-sm tracking-[0.35em] text-muted-foreground uppercase">
                                    Integrated Workflow
                                </p>
                                <h2 className="text-3xl font-semibold">
                                    One lifecycle for institution exams, LMS
                                    delivery, and national in-service cycles.
                                </h2>
                                <p className="text-muted-foreground">
                                    Build assets once, reuse across
                                    organizations, and surface insights the
                                    moment attempts finish.
                                </p>
                            </div>
                            <div className="grid gap-4 text-sm">
                                {workflow.map((step, index) => (
                                    <div
                                        key={step.title}
                                        className="flex items-center gap-4 rounded-2xl border border-border/70 bg-background/72 p-4 shadow-[0_16px_32px_-28px_rgb(35_24_74_/_0.16)] backdrop-blur-sm dark:bg-background/16 dark:shadow-[0_16px_32px_-28px_rgb(0_0_0_/_0.45)]"
                                    >
                                        <span className="flex h-10 w-10 items-center justify-center rounded-xl border border-primary/20 bg-primary/8 text-lg font-semibold text-primary">
                                            {index + 1}
                                        </span>
                                        <div>
                                            <p className="font-medium">
                                                {step.title}
                                            </p>
                                            <p className="text-muted-foreground">
                                                {step.description}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                        <div className="mt-8 flex flex-wrap items-center gap-3 text-xs tracking-[0.35em] text-muted-foreground uppercase">
                            <span className="inline-flex items-center gap-2 rounded-full border border-border/70 bg-background/72 px-4 py-1 text-foreground dark:bg-background/16">
                                <ClipboardList className="h-4 w-4" />
                                Institution Exams
                            </span>
                            <span className="inline-flex items-center gap-2 rounded-full border border-border/70 bg-background/72 px-4 py-1 text-foreground dark:bg-background/16">
                                <GraduationCap className="h-4 w-4" />
                                LMS Delivery
                            </span>
                            <span className="inline-flex items-center gap-2 rounded-full border border-border/70 bg-background/72 px-4 py-1 text-foreground dark:bg-background/16">
                                <ShieldCheck className="h-4 w-4" />
                                In-Service Drills
                            </span>
                        </div>
                    </section>

                    <section className="rounded-3xl border border-border bg-card p-8 shadow-sm">
                        <div className="grid gap-8 lg:grid-cols-2">
                            <div className="space-y-4">
                                <p className="text-sm tracking-[0.3em] text-muted-foreground uppercase">
                                    Ready to modernize residency?
                                </p>
                                <h2 className="text-3xl font-semibold">
                                    UNIFIED-LMS brings institution exams, LMS tasks,
                                    and in-service standards together so every
                                    resident can prove mastery.
                                </h2>
                                <p className="text-muted-foreground">
                                    Whether you are a national board, teaching
                                    hospital, or training program, you can run
                                    the same playbook with confidence.
                                </p>
                            </div>
                            <div className="space-y-4">
                                <div className="rounded-2xl border border-border bg-background p-4">
                                    <p className="text-sm font-medium">
                                        System Admin + Admin visibility
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Residents directory is reserved for
                                        leadership, keeping sensitive data out
                                        of general staff views.
                                    </p>
                                </div>
                                <div className="rounded-2xl border border-border bg-background p-4">
                                    <p className="text-sm font-medium">
                                        Institution + National support
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Switch between local organizations and
                                        national in-service contexts without
                                        leaving the platform.
                                    </p>
                                </div>
                                <div className="rounded-2xl border border-border bg-background p-4">
                                    <p className="text-sm font-medium">
                                        Evidence-based reporting
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Generate audit-ready packets showing
                                        exam coverage, outcomes, and remediation
                                        plans.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div className="mt-8 flex flex-wrap items-center gap-4 border-t border-border pt-6 text-sm text-muted-foreground">
                            <div className="inline-flex items-center gap-2">
                                <BarChart3 className="h-4 w-4 text-primary" />
                                Resident analytics included
                            </div>
                            <div className="inline-flex items-center gap-2">
                                <ShieldCheck className="h-4 w-4 text-emerald-500" />
                                Secure by design
                            </div>
                            <div className="inline-flex items-center gap-2">
                                <Users className="h-4 w-4 text-sky-500" />
                                Collaboration-ready for every cohort
                            </div>
                        </div>
                    </section>
                </main>
            </div>
        </>
    );
}
