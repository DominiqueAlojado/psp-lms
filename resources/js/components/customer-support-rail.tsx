import { Button } from '@/components/ui/button';
import { preserveOrgParam } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { LifeBuoy } from 'lucide-react';

export function CustomerSupportRail() {
    const page = usePage<SharedData>();
    const currentOrgSlug = page.props.auth.currentOrganization?.slug;
    const isInServiceExamTakePage =
        /^\/exams\/inservice\/[^/]+\/take(?:\?|$)/.test(page.url);
    const isInstitutionExamTakePage =
        /^\/exams\/institution\/[^/]+\/take(?:\?|$)/.test(page.url);

    if (isInServiceExamTakePage || isInstitutionExamTakePage) {
        return null;
    }

    return (
        <div className="pointer-events-none fixed right-0 top-1/2 z-40 -translate-y-1/2 pr-3">
            <Button
                asChild
                className="pointer-events-auto h-auto rounded-[1.35rem] border border-white/10 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-primary)_88%,#1b1234),color-mix(in_oklab,var(--color-primary)_70%,#d946ef))] px-3 py-4 text-white shadow-[0_26px_46px_-28px_rgb(96_44_193_/_0.52)] hover:brightness-[1.04] dark:border-white/8 dark:shadow-[0_26px_48px_-28px_rgb(59_27_135_/_0.72)]"
            >
                <Link
                    href={preserveOrgParam('/support', currentOrgSlug)}
                    className="flex flex-col items-center gap-3"
                >
                    <LifeBuoy className="size-5" />
                    <span className="[writing-mode:vertical-rl] rotate-180 text-sm font-semibold tracking-[0.04em]">
                        Customer Support
                    </span>
                </Link>
            </Button>
        </div>
    );
}
