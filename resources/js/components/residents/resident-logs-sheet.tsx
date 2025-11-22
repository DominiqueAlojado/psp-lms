import { Badge } from '@/components/ui/badge';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Activity, Clock, FileText, User } from 'lucide-react';
import { useEffect, useState } from 'react';

interface ActivityLog {
    id: number;
    description: string;
    log_name: string;
    event: string;
    properties: {
        attributes?: Record<string, any>;
        old?: Record<string, any>;
    };
    causer?: {
        id: number;
        name: string;
        email: string;
    };
    subject?: {
        id: number;
        type: string;
    };
    created_at: string;
}

interface ResidentLogsSheetProps {
    resident: {
        id: number;
        full_name: string;
        email: string;
    } | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function ResidentLogsSheet({
    resident,
    open,
    onOpenChange,
}: ResidentLogsSheetProps) {
    const [logs, setLogs] = useState<ActivityLog[]>([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (resident && open) {
            setLoading(true);
            fetch(`/residents/${resident.id}/logs`)
                .then((res) => res.json())
                .then((data) => {
                    setLogs(data.logs || []);
                    setLoading(false);
                })
                .catch((error) => {
                    console.error('Error fetching activity logs:', error);
                    setLoading(false);
                });
        } else {
            setLogs([]);
        }
    }, [resident, open]);

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const getLogNameBadge = (logName: string) => {
        const colors: Record<string, string> = {
            submissions:
                'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            assessments:
                'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
            users: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            exam_attempts:
                'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            organizations:
                'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
            residents:
                'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200',
        };

        return (
            <Badge
                variant="outline"
                className={colors[logName] || 'bg-gray-100 text-gray-800'}
            >
                {logName}
            </Badge>
        );
    };

    const formatProperties = (properties: ActivityLog['properties']) => {
        if (!properties || !properties.attributes) {
            return null;
        }

        const changes: string[] = [];
        const attrs = properties.attributes;
        const old = properties.old || {};

        Object.keys(attrs).forEach((key) => {
            const newValue = attrs[key];
            const oldValue = old[key];

            if (newValue !== oldValue) {
                // Handle array values (like roles, organizations)
                if (Array.isArray(newValue) || Array.isArray(oldValue)) {
                    const oldArray = Array.isArray(oldValue) ? oldValue : [];
                    const newArray = Array.isArray(newValue) ? newValue : [];

                    if (
                        JSON.stringify(oldArray.sort()) !==
                        JSON.stringify(newArray.sort())
                    ) {
                        changes.push(
                            `${key}: [${oldArray.join(', ') || 'none'}] → [${newArray.join(', ') || 'none'}]`,
                        );
                    }
                } else if (oldValue !== undefined) {
                    changes.push(`${key}: ${oldValue} → ${newValue}`);
                } else {
                    changes.push(`${key}: ${newValue}`);
                }
            }
        });

        return changes.length > 0 ? changes : null;
    };

    if (!resident) return null;

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent className="overflow-hidden p-0 sm:max-w-3xl">
                <SheetHeader className="border-b p-6">
                    <div className="flex items-center gap-3">
                        <Activity className="h-5 w-5" />
                        <div className="flex-1">
                            <SheetTitle>Activity Logs</SheetTitle>
                            <SheetDescription>
                                Activity history for {resident.full_name} (
                                {resident.email})
                            </SheetDescription>
                        </div>
                    </div>
                </SheetHeader>

                <ScrollArea className="h-[calc(100vh-120px)]">
                    <div className="p-6">
                        {loading ? (
                            <div className="py-12 text-center">
                                <div className="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-current border-r-transparent"></div>
                                <p className="mt-4 text-sm text-muted-foreground">
                                    Loading activity logs...
                                </p>
                            </div>
                        ) : logs.length === 0 ? (
                            <div className="py-12 text-center">
                                <FileText className="mx-auto h-12 w-12 text-muted-foreground" />
                                <p className="mt-4 text-sm font-medium">
                                    No activity logs found
                                </p>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    This resident has no recorded activities
                                    yet.
                                </p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                <div className="rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>
                                                    Description
                                                </TableHead>
                                                <TableHead>Type</TableHead>
                                                <TableHead>
                                                    Changed By
                                                </TableHead>
                                                <TableHead>Changes</TableHead>
                                                <TableHead>Date</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {logs.map((log) => {
                                                const changes =
                                                    formatProperties(
                                                        log.properties,
                                                    );

                                                return (
                                                    <TableRow key={log.id}>
                                                        <TableCell>
                                                            <div className="flex items-center gap-2">
                                                                <Activity className="h-4 w-4 text-muted-foreground" />
                                                                <span className="font-medium">
                                                                    {
                                                                        log.description
                                                                    }
                                                                </span>
                                                            </div>
                                                        </TableCell>
                                                        <TableCell>
                                                            {getLogNameBadge(
                                                                log.log_name,
                                                            )}
                                                        </TableCell>
                                                        <TableCell>
                                                            {log.causer ? (
                                                                <div className="flex items-center gap-2">
                                                                    <User className="h-3 w-3 text-muted-foreground" />
                                                                    <div className="flex flex-col">
                                                                        <span className="text-sm font-medium">
                                                                            {
                                                                                log
                                                                                    .causer
                                                                                    .name
                                                                            }
                                                                        </span>
                                                                        <span className="text-xs text-muted-foreground">
                                                                            {
                                                                                log
                                                                                    .causer
                                                                                    .email
                                                                            }
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            ) : (
                                                                <span className="text-sm text-muted-foreground italic">
                                                                    System
                                                                </span>
                                                            )}
                                                        </TableCell>
                                                        <TableCell>
                                                            {changes ? (
                                                                <div className="space-y-1">
                                                                    {changes.map(
                                                                        (
                                                                            change,
                                                                            idx,
                                                                        ) => (
                                                                            <div
                                                                                key={
                                                                                    idx
                                                                                }
                                                                                className="text-xs text-muted-foreground"
                                                                            >
                                                                                {
                                                                                    change
                                                                                }
                                                                            </div>
                                                                        ),
                                                                    )}
                                                                </div>
                                                            ) : (
                                                                <span className="text-xs text-muted-foreground">
                                                                    No changes
                                                                </span>
                                                            )}
                                                        </TableCell>
                                                        <TableCell>
                                                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                                                <Clock className="h-3 w-3" />
                                                                {formatDate(
                                                                    log.created_at,
                                                                )}
                                                            </div>
                                                        </TableCell>
                                                    </TableRow>
                                                );
                                            })}
                                        </TableBody>
                                    </Table>
                                </div>

                                <div className="text-center text-sm text-muted-foreground">
                                    Showing {logs.length} activity log
                                    {logs.length !== 1 ? 's' : ''}
                                </div>
                            </div>
                        )}
                    </div>
                </ScrollArea>
            </SheetContent>
        </Sheet>
    );
}
