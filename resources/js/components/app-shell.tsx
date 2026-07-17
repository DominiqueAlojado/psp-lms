import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { SidebarProvider } from '@/components/ui/sidebar';
import { Toaster } from '@/components/ui/sonner';
import { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { Info } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

interface AppShellProps {
    children: React.ReactNode;
    variant?: 'header' | 'sidebar';
}

export function AppShell({ children, variant = 'header' }: AppShellProps) {
    const page = usePage<SharedData>();
    const isOpen = page.props.sidebarOpen;
    const auth = page.props.auth;
    const [showResidentDemoDialog, setShowResidentDemoDialog] = useState(false);
    const residentDemoNotice =
        'Demo notice: the data shown in this resident portal is for demonstration purposes only and may include dummy sample content.';
    const residentDemoNoticeVersion = 'v2-modal';

    // Handle flash messages from Laravel
    useEffect(() => {
        const flash = page.props.flash as
            | {
                  success?: string;
                  error?: string;
                  info?: string;
                  warning?: string;
              }
            | undefined;

        if (flash?.success) {
            toast.success(flash.success);
        }

        if (flash?.error) {
            toast.error(flash.error);
        }

        if (flash?.info) {
            toast.info(flash.info);
        }

        if (flash?.warning) {
            toast.warning(flash.warning);
        }
    }, [page.props.flash]);

    useEffect(() => {
        if (!auth?.user || !Array.isArray(auth.roles)) {
            return;
        }

        if (!auth.roles.includes('Resident')) {
            return;
        }

        const storageKey = `resident-demo-notice:${residentDemoNoticeVersion}:${auth.user.id}`;

        if (window.sessionStorage.getItem(storageKey)) {
            return;
        }

        const openTimer = window.setTimeout(() => {
            setShowResidentDemoDialog(true);
        }, 0);

        return () => window.clearTimeout(openTimer);
    }, [auth, residentDemoNotice, residentDemoNoticeVersion]);

    const handleResidentDemoAcknowledge = () => {
        if (auth?.user) {
            const storageKey = `resident-demo-notice:${residentDemoNoticeVersion}:${auth.user.id}`;
            window.sessionStorage.setItem(storageKey, 'acknowledged');
        }

        setShowResidentDemoDialog(false);
    };

    if (variant === 'header') {
        return (
            <div className="flex min-h-screen w-full flex-col">
                {children}
                <Dialog
                    open={showResidentDemoDialog}
                    onOpenChange={setShowResidentDemoDialog}
                >
                    <DialogContent className="max-w-md rounded-2xl p-0 overflow-hidden">
                        <div className="bg-[linear-gradient(135deg,color-mix(in_oklab,var(--color-primary)_18%,white),color-mix(in_oklab,var(--color-primary)_8%,var(--color-background)))] px-6 py-5 dark:bg-[linear-gradient(135deg,color-mix(in_oklab,var(--color-primary)_26%,black),color-mix(in_oklab,var(--color-primary)_12%,var(--color-background)))]">
                            <DialogHeader className="text-left">
                                <div className="mb-3 flex h-11 w-11 items-center justify-center rounded-2xl bg-primary/12 text-primary">
                                    <Info className="h-5 w-5" />
                                </div>
                                <DialogTitle>Demo Environment Notice</DialogTitle>
                                <DialogDescription className="text-sm leading-6">
                                    This resident portal is currently using demo data for presentation purposes.
                                </DialogDescription>
                            </DialogHeader>
                        </div>
                        <div className="px-6 py-5">
                            <div className="rounded-xl border border-primary/10 bg-primary/5 px-4 py-3 text-sm text-muted-foreground">
                                Sample exams, grades, and related records may be dummy content and should not be treated as live production data.
                            </div>
                            <DialogFooter className="mt-5">
                                <Button
                                    type="button"
                                    className="w-full sm:w-auto"
                                    onClick={handleResidentDemoAcknowledge}
                                >
                                    I Understand
                                </Button>
                            </DialogFooter>
                        </div>
                    </DialogContent>
                </Dialog>
                <Toaster />
            </div>
        );
    }

    return (
        <SidebarProvider defaultOpen={isOpen}>
            {children}
            <Dialog
                open={showResidentDemoDialog}
                onOpenChange={setShowResidentDemoDialog}
            >
                <DialogContent className="max-w-md overflow-hidden rounded-2xl p-0">
                    <div className="bg-[linear-gradient(135deg,color-mix(in_oklab,var(--color-primary)_18%,white),color-mix(in_oklab,var(--color-primary)_8%,var(--color-background)))] px-6 py-5 dark:bg-[linear-gradient(135deg,color-mix(in_oklab,var(--color-primary)_26%,black),color-mix(in_oklab,var(--color-primary)_12%,var(--color-background)))]">
                        <DialogHeader className="text-left">
                            <div className="mb-3 flex h-11 w-11 items-center justify-center rounded-2xl bg-primary/12 text-primary">
                                <Info className="h-5 w-5" />
                            </div>
                            <DialogTitle>Demo Environment Notice</DialogTitle>
                            <DialogDescription className="text-sm leading-6">
                                This resident portal is currently using demo data for presentation purposes.
                            </DialogDescription>
                        </DialogHeader>
                    </div>
                    <div className="px-6 py-5">
                        <div className="rounded-xl border border-primary/10 bg-primary/5 px-4 py-3 text-sm text-muted-foreground">
                            Sample exams, grades, and related records may be dummy content and should not be treated as live production data.
                        </div>
                        <DialogFooter className="mt-5">
                            <Button
                                type="button"
                                className="w-full sm:w-auto"
                                onClick={handleResidentDemoAcknowledge}
                            >
                                I Understand
                            </Button>
                        </DialogFooter>
                    </div>
                </DialogContent>
            </Dialog>
            <Toaster />
        </SidebarProvider>
    );
}
