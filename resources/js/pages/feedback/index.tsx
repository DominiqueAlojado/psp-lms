import HeadingSmall from '@/components/heading-small';
import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
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
import { useState } from 'react';

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
        id: 'overall',
        title: 'Overall Experience',
        description: 'How did the platform feel during your workflow today?',
        icon: Sparkles,
    },
    {
        id: 'content',
        title: 'Learning Content',
        description: 'Was the content useful, clear, and relevant to your training?',
        icon: BookOpenText,
    },
    {
        id: 'support',
        title: 'Support and Guidance',
        description: 'Did you feel supported when you needed help or direction?',
        icon: HeartHandshake,
    },
    {
        id: 'usability',
        title: 'Ease of Use',
        description: 'Was it easy to navigate exams, grades, and learning tools?',
        icon: CircleHelp,
    },
] as const;

type FeedbackAreaId = (typeof feedbackAreas)[number]['id'];

export default function FeedbackDesignPage() {
    const [ratings, setRatings] = useState<Record<FeedbackAreaId, number>>({
        overall: 3,
        content: 4,
        support: 3,
        usability: 3,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Feedback" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <HeadingSmall
                    title="Share Your Feedback"
                    description="A design-first feedback module where residents can quickly rate their experience and leave thoughtful suggestions."
                />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        title="Quick to Answer"
                        value="2 min"
                        description="Short enough for end-of-session feedback"
                        icon={MessageSquareText}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Icon Ratings"
                        value="4"
                        description="Simple visual choices instead of text-heavy scales"
                        icon={Sparkles}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Focus Areas"
                        value={feedbackAreas.length}
                        description="Experience, content, support, and usability"
                        icon={BookOpenText}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Submission Style"
                        value="Guided"
                        description="Structured ratings with open comments"
                        icon={Send}
                        iconColor="text-primary"
                    />
                </div>

                <Card className="overflow-hidden border-primary/10 bg-[linear-gradient(135deg,rgba(248,244,255,0.98),rgba(255,255,255,0.94))]">
                    <CardContent className="flex flex-col gap-4 p-6 lg:flex-row lg:items-center lg:justify-between">
                        <div className="space-y-1">
                            <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                Feedback module concept
                            </p>
                            <h3 className="text-2xl font-semibold tracking-[-0.04em] text-foreground">
                                Friendly, visual, and low-friction for residents
                            </h3>
                            <p className="text-sm leading-6 text-muted-foreground">
                                This concept keeps the form lightweight by using expressive icons for ratings, clear sections, and one focused comment area.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Badge variant="secondary">Design preview</Badge>
                            <Badge variant="outline">No backend wiring yet</Badge>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-6 xl:grid-cols-[1.3fr_0.7fr]">
                    <Card className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.94))]">
                        <CardHeader className="pb-3">
                            <CardTitle>Rate Your Experience</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="grid gap-4 md:grid-cols-2">
                                {feedbackAreas.map((area) => {
                                    const AreaIcon = area.icon;

                                    return (
                                        <div
                                            key={area.id}
                                            className="rounded-[1.5rem] border border-border/70 bg-background/85 p-5"
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
                                                    const isActive =
                                                        ratings[area.id] ===
                                                        rating.value;

                                                    return (
                                                        <button
                                                            key={rating.value}
                                                            type="button"
                                                            onClick={() =>
                                                                setRatings(
                                                                    (
                                                                        current,
                                                                    ) => ({
                                                                        ...current,
                                                                        [area.id]:
                                                                            rating.value,
                                                                    }),
                                                                )
                                                            }
                                                            className={`flex min-w-[4.5rem] flex-col items-center gap-2 rounded-2xl border px-4 py-3 text-center transition ${
                                                                isActive
                                                                    ? 'border-primary/35 bg-[linear-gradient(180deg,rgba(139,92,246,0.14),rgba(255,255,255,0.96))] text-primary shadow-[0_14px_32px_-24px_rgb(96_44_193_/_0.45)]'
                                                                    : 'border-border/70 bg-background text-muted-foreground hover:border-primary/20 hover:bg-primary/5 hover:text-foreground'
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

                            <div className="rounded-[1.5rem] border border-border/70 bg-background/85 p-5">
                                <div className="mb-3 space-y-1">
                                    <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                        Written feedback
                                    </p>
                                    <h3 className="font-semibold text-foreground">
                                        Tell us what would make this better
                                    </h3>
                                </div>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <Input placeholder="What were you doing when you formed this feedback?" />
                                    <Input placeholder="Which module or page is this about?" />
                                </div>
                                <Textarea
                                    className="mt-4 min-h-36"
                                    placeholder="Share your suggestions, confusion points, bugs, or anything that felt especially helpful."
                                />
                            </div>

                            <div className="flex flex-col gap-3 rounded-[1.5rem] border border-dashed border-primary/20 bg-primary/5 p-4 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p className="font-medium text-foreground">
                                        Recommend this experience to another resident?
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        This could later become a simple yes/no or icon-based referral sentiment.
                                    </p>
                                </div>
                                <div className="flex gap-2">
                                    <Button
                                        type="button"
                                        className="border border-border/80 bg-white text-foreground shadow-[0_10px_24px_-20px_rgb(27_31_59_/_0.22)] hover:bg-accent"
                                        variant="outline"
                                    >
                                        Not Yet
                                    </Button>
                                    <Button
                                        type="button"
                                        className="border-transparent bg-[linear-gradient(135deg,#7c3aed,#c026d3)] text-white shadow-[0_18px_36px_-22px_rgb(124_58_237_/_0.58)] hover:brightness-[1.03]"
                                    >
                                        Yes, I would
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="space-y-6">
                        <Card className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.94))]">
                            <CardHeader className="pb-3">
                                <CardTitle>Why This Design Works</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm leading-6 text-muted-foreground">
                                <p>
                                    Residents can answer quickly without reading a dense numeric scale.
                                </p>
                                <p>
                                    Icon ratings feel lighter and more approachable on mobile and desktop.
                                </p>
                                <p>
                                    The comment area still leaves room for detailed feedback when needed.
                                </p>
                            </CardContent>
                        </Card>

                        <Card className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.94))]">
                            <CardHeader className="pb-3">
                                <CardTitle>Suggested Fields</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                <Badge variant="outline">Overall experience</Badge>
                                <Badge variant="outline">Ease of use</Badge>
                                <Badge variant="outline">Content quality</Badge>
                                <Badge variant="outline">Support quality</Badge>
                                <Badge variant="outline">Open feedback</Badge>
                                <Badge variant="outline">Recommendation intent</Badge>
                            </CardContent>
                        </Card>

                        <Card className="overflow-hidden border-primary/10 bg-[linear-gradient(160deg,rgba(255,248,240,0.98),rgba(255,255,255,0.95))]">
                            <CardContent className="space-y-3 p-5">
                                <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                    Next step
                                </p>
                                <h3 className="text-lg font-semibold text-foreground">
                                    Ready to evolve into a real module
                                </h3>
                                <p className="text-sm leading-6 text-muted-foreground">
                                    Once you like the design, we can wire this into a submit flow, admin review dashboard, and analytics summary for feedback trends.
                                </p>
                                <Button
                                    type="button"
                                    className="w-full border-transparent bg-[linear-gradient(135deg,#7c3aed,#c026d3)] text-white shadow-[0_18px_36px_-22px_rgb(124_58_237_/_0.58)] hover:brightness-[1.03]"
                                >
                                    Submit Feedback
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
