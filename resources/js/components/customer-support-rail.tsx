import { Button } from '@/components/ui/button';
import { preserveOrgParam } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { LifeBuoy } from 'lucide-react';

export function CustomerSupportRail() {
    const page = usePage<SharedData>();
    const currentOrgSlug = page.props.auth.currentOrganization?.slug;

    return (
        <div className="pointer-events-none fixed right-0 top-1/2 z-40 -translate-y-1/2 pr-3">
            <Button
                asChild
                className="pointer-events-auto h-auto rounded-[1.25rem] border-transparent bg-[linear-gradient(180deg,#7c3aed,#a855f7)] px-3 py-4 text-white shadow-[0_24px_44px_-28px_rgb(124_58_237_/_0.6)] hover:brightness-[1.03]"
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
