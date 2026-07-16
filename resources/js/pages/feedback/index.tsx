import HeadingSmall from '@/components/heading-small';
import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { preserveOrgParam } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import {
    BookOpenText,
    CircleHelp,
    Frown,
    HeartHandshake,
    Laugh,
    Meh,
    MessageSquareText,
    Send,
    Smile,
    Sparkles,
} from 'lucide-react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Feedback',
        href: '/feedback',
    },
];

const ratingIcons = [
    { value: 1, icon: Frown, label: 'Very poor' },
    { value: 2, icon: Meh, label: 'Needs work' },
    { value: 3, icon: Smile, label: 'Good' },
    { value: 4, icon: Laugh, label: 'Excellent' },
] as const;

const feedbackAreas = [
    {
        id: 'overall_rating',
        title: 'Overall Experience',
        description: 'How did the platform feel during your workflow today?',
        icon: Sparkles,
    },
    {
        id: 'content_rating',
        title: 'Learning Content',
        description: 'Was the content useful, clear, and relevant to your training?',
        icon: BookOpenText,
    },
    {
        id: 'support_rating',
        title: 'Support and Guidance',
        description: 'Did you feel supported when you needed help or direction?',
        icon: HeartHandshake,
    },
    {
        id: 'usability_rating',
        title: 'Ease of Use',
        description: 'Was it easy to navigate exams, grades, and learning tools?',
        icon: CircleHelp,
    },
] as const;

type FeedbackAreaId = (typeof feedbackAreas)[number]['id'];

interface FeedbackEntry {
    id: number;
    organization_name: string | null;
    user_name: string | null;
    overall_rating: number;
    content_rating: number;
    support_rating: number;
    usability_rating: number;
    context: string | null;
    module_name: string | null;
    page_url: string | null;
    comment: string;
    would_recommend: boolean | null;
    created_at: string;
    created_at_human: string;
}

interface PageProps {
    entries: FeedbackEntry[];
    summary: {
        total_feedback: number;
        average_overall: number;
        average_content: number;
        recommendation_rate: number;
    };
    canCreateFeedback: boolean;
    isAllOrganizationsContext: boolean;
}

const renderRatingPills = (value: number) => {
    return (
        <div className="flex gap-1.5">
            {ratingIcons.map((rating) => {
                const RatingIcon = rating.icon;

                return (
                    <div
                        key={rating.value}
                        className={`flex size-8 items-center justify-center rounded-xl border ${
                            value === rating.value
                                ? 'border-primary/30 bg-primary/10 text-primary'
                                : 'border-border/70 bg-background/70 text-muted-foreground'
                        }`}
                    >
                        <RatingIcon className="size-4" />
                    </div>
                );
            })}
        </div>
    );
};

export default function FeedbackIndex({
    entries,
    summary,
    canCreateFeedback,
    isAllOrganizationsContext,
}: PageProps) {
    const page = usePage<SharedData>();
    const currentOrgSlug = page.props.auth.currentOrganization?.slug;
    const form = useForm({
        overall_rating: 3,
        content_rating: 3,
        support_rating: 3,
        usability_rating: 3,
        context: '',
        module_name: '',
        page_url: '',
        comment: '',
        would_recommend: true as boolean | null,
    });

    const setRating = (field: FeedbackAreaId, value: number) => {
        form.setData(field, value);
    };

    const submit = () => {
        if (!canCreateFeedback) {
            toast.error('Select a specific organization before submitting feedback.');
            return;
        }

        form.post(preserveOrgParam('/feedback', currentOrgSlug), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Feedback submitted successfully!');
                form.reset('context', 'module_name', 'page_url', 'comment');
                form.setData({
                    overall_rating: 3,
                    content_rating: 3,
                    support_rating: 3,
                    usability_rating: 3,
                    context: '',
                    module_name: '',
                    page_url: '',
                    comment: '',
                    would_recommend: true,
                });
            },
            onError: () => {
                toast.error('Please check the feedback form.');
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Feedback" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <HeadingSmall
                    title="Share Your Feedback"
                    description="Rate your experience, leave suggestions, and help improve the resident learning workflow."
                />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        title="Feedback Received"
                        value={summary.total_feedback}
                        description="Recent submissions in this scope"
                        icon={MessageSquareText}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Overall Average"
                        value={summary.average_overall > 0 ? `${summary.average_overall.toFixed(1)}/4` : 'N/A'}
                        description="Average experience rating"
                        icon={Sparkles}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Content Average"
                        value={summary.average_content > 0 ? `${summary.average_content.toFixed(1)}/4` : 'N/A'}
                        description="Learning-content rating"
                        icon={BookOpenText}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Recommend Rate"
                        value={`${summary.recommendation_rate.toFixed(1)}%`}
                        description="Respondents who would recommend it"
                        icon={HeartHandshake}
                        iconColor="text-primary"
                    />
                </div>

                {isAllOrganizationsContext && (
                    <Card className="border-primary/12 bg-[linear-gradient(135deg,color-mix(in_oklab,var(--color-accent)_88%,white)_0%,color-mix(in_oklab,var(--color-card)_96%,var(--color-accent))_100%)] dark:bg-[linear-gradient(135deg,color-mix(in_oklab,var(--color-accent)_72%,black)_0%,color-mix(in_oklab,var(--color-card)_92%,var(--color-accent))_100%)]">
                        <CardContent className="p-6">
                            <p className="text-sm leading-6 text-muted-foreground">
                                You are viewing feedback across all organizations. Choose a specific organization in the switcher to submit new feedback.
                            </p>
                        </CardContent>
                    </Card>
                )}

                <div className="grid gap-6 xl:grid-cols-[1.25fr_0.75fr]">
                    <Card className="overflow-hidden border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))]">
                        <CardHeader className="pb-3">
                            <CardTitle>Submit Feedback</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="grid gap-4 md:grid-cols-2">
                                {feedbackAreas.map((area) => {
                                    const AreaIcon = area.icon;
                                    const currentValue = form.data[area.id];

                                    return (
                                        <div
                                            key={area.id}
                                            className="rounded-[1.5rem] border border-border/75 bg-background/88 p-5 shadow-[0_18px_34px_-30px_rgb(35_24_74_/_0.16)] dark:shadow-[0_18px_34px_-28px_rgb(0_0_0_/_0.42)]"
                                        >
                                            <div className="mb-4 flex items-start gap-3">
                                                <div className="flex size-11 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                                    <AreaIcon className="size-5" />
                                                </div>
                                                <div className="space-y-1">
                                                    <h3 className="font-semibold text-foreground">
                                                        {area.title}
                                                    </h3>
                                                    <p className="text-sm leading-6 text-muted-foreground">
                                                        {area.description}
                                                    </p>
                                                </div>
                                            </div>

                                            <div className="flex flex-wrap gap-2">
                                                {ratingIcons.map((rating) => {
                                                    const RatingIcon = rating.icon;
                                                    const isActive = currentValue === rating.value;

                                                    return (
                                                        <button
                                                            key={rating.value}
                                                            type="button"
                                                            onClick={() => setRating(area.id, rating.value)}
                                                            className={`flex min-w-[4.5rem] flex-col items-center gap-2 rounded-2xl border px-4 py-3 text-center transition ${
                                                                isActive
                                                                    ? 'border-primary/35 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-primary)_16%,var(--color-card)),color-mix(in_oklab,var(--color-card)_96%,white))] text-primary shadow-[0_14px_32px_-24px_rgb(96_44_193_/_0.35)] dark:bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-primary)_20%,var(--color-card)),color-mix(in_oklab,var(--color-card)_92%,var(--color-accent)))]'
                                                                    : 'border-border/75 bg-background/92 text-muted-foreground hover:border-primary/20 hover:bg-primary/6 hover:text-foreground'
                                                            }`}
                                                            aria-label={`${area.title}: ${rating.label}`}
                                                        >
                                                            <RatingIcon className="size-5" />
                                                            <span className="text-xs font-medium">
                                                                {rating.label}
                                                            </span>
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>

                            <div className="rounded-[1.5rem] border border-border/75 bg-background/88 p-5 shadow-[0_18px_34px_-30px_rgb(35_24_74_/_0.16)] dark:shadow-[0_18px_34px_-28px_rgb(0_0_0_/_0.42)]">
                                <div className="mb-3 space-y-1">
                                    <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                        Feedback details
                                    </p>
                                    <h3 className="font-semibold text-foreground">
                                        Tell us what happened and what would help
                                    </h3>
                                </div>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <Input
                                        value={form.data.context}
                                        onChange={(event) => form.setData('context', event.target.value)}
                                        placeholder="What were you doing when you formed this feedback?"
                                    />
                                    <Input
                                        value={form.data.module_name}
                                        onChange={(event) => form.setData('module_name', event.target.value)}
                                        placeholder="Which module or page is this about?"
                                    />
                                </div>
                                <Input
                                    className="mt-4"
                                    value={form.data.page_url}
                                    onChange={(event) => form.setData('page_url', event.target.value)}
                                    placeholder="Optional page URL, e.g. /my-grades"
                                />
                                <Textarea
                                    className="mt-4 min-h-36"
                                    value={form.data.comment}
                                    onChange={(event) => form.setData('comment', event.target.value)}
                                    placeholder="Share your suggestions, bugs, confusion points, or anything that felt especially helpful."
                                />
                                {form.errors.comment && (
                                    <p className="mt-2 text-sm text-destructive">{form.errors.comment}</p>
                                )}
                            </div>

                            <div className="flex flex-col gap-3 rounded-[1.5rem] border border-dashed border-primary/20 bg-primary/5 p-4 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p className="font-medium text-foreground">
                                        Would you recommend this experience to another resident?
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        This helps us understand overall confidence in the workflow.
                                    </p>
                                </div>
                                <div className="flex gap-2">
                                    <Button
                                        type="button"
                                        variant={form.data.would_recommend === false ? 'default' : 'outline'}
                                        className={form.data.would_recommend === false ? '' : 'border border-border/80 bg-background/94 text-foreground'}
                                        onClick={() => form.setData('would_recommend', false)}
                                    >
                                        Not Yet
                                    </Button>
                                    <Button
                                        type="button"
                                        variant={form.data.would_recommend === true ? 'default' : 'outline'}
                                        className={form.data.would_recommend === true ? 'border-transparent bg-[linear-gradient(135deg,#7c3aed,#c026d3)] text-white' : ''}
                                        onClick={() => form.setData('would_recommend', true)}
                                    >
                                        Yes, I would
                                    </Button>
                                </div>
                            </div>

                            <Button
                                type="button"
                                className="w-full border-transparent bg-[linear-gradient(135deg,#7c3aed,#c026d3)] text-white shadow-[0_18px_36px_-22px_rgb(124_58_237_/_0.58)] hover:brightness-[1.03]"
                                disabled={form.processing || !canCreateFeedback}
                                onClick={submit}
                            >
                                <Send className="mr-2 h-4 w-4" />
                                {form.processing ? 'Submitting...' : 'Submit Feedback'}
                            </Button>
                        </CardContent>
                    </Card>

                    <div className="space-y-6">
                        <Card className="overflow-hidden border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))]">
                            <CardHeader className="pb-3">
                                <CardTitle>Recent Feedback</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {entries.length === 0 ? (
                                    <div className="rounded-2xl border border-dashed border-border/80 px-4 py-8 text-center text-sm text-muted-foreground">
                                        No feedback submitted yet in this scope.
                                    </div>
                                ) : (
                                    entries.map((entry) => (
                                        <div
                                            key={entry.id}
                                            className="rounded-2xl border border-border/75 bg-background/88 p-4"
                                        >
                                            <div className="flex flex-wrap items-center gap-2">
                                                {entry.organization_name && (
                                                    <Badge variant="outline">{entry.organization_name}</Badge>
                                                )}
                                                {entry.module_name && (
                                                    <Badge variant="outline">{entry.module_name}</Badge>
                                                )}
                                                {entry.would_recommend !== null && (
                                                    <Badge variant={entry.would_recommend ? 'secondary' : 'outline'}>
                                                        {entry.would_recommend ? 'Would recommend' : 'Would not recommend yet'}
                                                    </Badge>
                                                )}
                                            </div>
                                            <p className="mt-3 text-sm leading-6 text-foreground">
                                                {entry.comment}
                                            </p>
                                            <div className="mt-4 space-y-3">
                                                <div>
                                                    <p className="mb-2 text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                                        Ratings
                                                    </p>
                                                    <div className="grid gap-3">
                                                        <div className="flex items-center justify-between gap-4">
                                                            <span className="text-sm text-muted-foreground">Overall</span>
                                                            {renderRatingPills(entry.overall_rating)}
                                                        </div>
                                                        <div className="flex items-center justify-between gap-4">
                                                            <span className="text-sm text-muted-foreground">Content</span>
                                                            {renderRatingPills(entry.content_rating)}
                                                        </div>
                                                        <div className="flex items-center justify-between gap-4">
                                                            <span className="text-sm text-muted-foreground">Support</span>
                                                            {renderRatingPills(entry.support_rating)}
                                                        </div>
                                                        <div className="flex items-center justify-between gap-4">
                                                            <span className="text-sm text-muted-foreground">Usability</span>
                                                            {renderRatingPills(entry.usability_rating)}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {entry.user_name ? `${entry.user_name} • ` : ''}
                                                    {entry.created_at_human}
                                                </div>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
