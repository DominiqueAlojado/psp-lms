import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { preserveOrgParam } from '@/lib/utils';
import type { SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, MonitorSmartphone, ShieldAlert } from 'lucide-react';

interface PageProps extends SharedData {
    message: string;
}

export default function ExamSessionConflict() {
    const { auth, message } = usePage<PageProps>().props;
    const currentOrgSlug = auth.currentOrganization?.slug;
    const examsUrl = preserveOrgParam('/resident-exams', currentOrgSlug);

    return (
        <AppLayout>
            <Head title="Exam Session Conflict" />

            <div className="flex h-full flex-1 items-center justify-center p-6">
                <div className="w-full max-w-3xl space-y-6">
                    <div className="space-y-3">
                        <div className="inline-flex items-center gap-2 rounded-md border px-3 py-1.5 text-sm text-muted-foreground">
                            <ShieldAlert className="h-4 w-4 text-amber-600" />
                            Exam access restricted
                        </div>
                        <h1 className="text-3xl font-semibold tracking-tight">
                            This exam is already open in another browser or device
                        </h1>
                        <p className="max-w-2xl text-sm leading-6 text-muted-foreground sm:text-base">
                            Only one active browser session is allowed per in-progress exam attempt.
                            Continue the exam from the original browser, or close that session before
                            opening it here.
                        </p>
                    </div>

                    <Alert className="border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100">
                        <AlertTriangle className="h-4 w-4" />
                        <AlertTitle>Session conflict detected</AlertTitle>
                        <AlertDescription>{message}</AlertDescription>
                    </Alert>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="rounded-lg border p-5">
                            <div className="mb-3 flex items-center gap-2">
                                <MonitorSmartphone className="h-4 w-4 text-primary" />
                                <h2 className="font-medium">What to do</h2>
                            </div>
                            <ul className="space-y-2 text-sm text-muted-foreground">
                                <li>Return to the browser or device where the exam was first opened.</li>
                                <li>Finish or close that active exam session there.</li>
                                <li>Come back here only after the original session is no longer active.</li>
                            </ul>
                        </div>

                        <div className="rounded-lg border p-5">
                            <div className="mb-3 flex items-center gap-2">
                                <ShieldAlert className="h-4 w-4 text-primary" />
                                <h2 className="font-medium">Why this happens</h2>
                            </div>
                            <p className="text-sm leading-6 text-muted-foreground">
                                This protection prevents one exam attempt from being used in multiple
                                browsers or devices at the same time.
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-col gap-3 sm:flex-row">
                        <Button asChild>
                            <Link href={typeof examsUrl === 'string' ? examsUrl : examsUrl.url}>
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Return to My Exams
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
