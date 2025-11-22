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

interface ActivityLogsSheetProps {
    entity: {
        id: number;
        name: string;
        email?: string;
        full_name?: string;
    } | null;
    fetchUrl: string;
    title: string;
    description?: string;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function ActivityLogsSheet({
    entity,
    fetchUrl,
    title,
    description,
    open,
    onOpenChange,
}: ActivityLogsSheetProps) {
    const [logs, setLogs] = useState<ActivityLog[]>([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (entity && open) {
            setLoading(true);
            fetch(fetchUrl, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            })
                .then((res) => {
                    if (!res.ok) {
                        throw new Error(`HTTP error! status: ${res.status}`);
                    }
                    return res.json();
                })
                .then((data) => {
                    console.log('Activity logs data received:', data);
                    setLogs(data.logs || []);
                    setLoading(false);
                })
                .catch((error) => {
                    console.error('Error fetching activity logs:', error);
                    console.error('Fetch URL:', fetchUrl);
                    setLogs([]);
                    setLoading(false);
                });
        } else {
            setLogs([]);
        }
    }, [entity, open, fetchUrl]);

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

        const formatValue = (value: any): string => {
            if (value === null || value === undefined) {
                return 'none';
            }
            if (Array.isArray(value)) {
                if (value.length === 0) {
                    return 'none';
                }
                // Handle arrays - convert each item to string properly
                const formattedItems = value.map((item) => {
                    if (typeof item === 'object' && item !== null) {
                        // If it's an object with name and email (like training officers)
                        if ('name' in item && 'email' in item) {
                            return `${item.name} (${item.email})`;
                        }
                        // If it has a name
                        if ('name' in item) {
                            return item.name;
                        }
                        // If it has an id
                        if ('id' in item) {
                            return String(item.id);
                        }
                        // For nested objects, try to stringify nicely
                        try {
                            const str = JSON.stringify(item);
                            // If it's a simple object, show it nicely
                            if (str.length < 100) {
                                return str;
                            }
                            return '[object]';
                        } catch {
                            return '[object]';
                        }
                    }
                    return String(item);
                });
                return `[${formattedItems.join(', ')}]`;
            }
            if (typeof value === 'boolean') {
                return value ? 'Yes' : 'No';
            }
            // Handle numeric booleans (1/0 from database) - but only for fields that are likely booleans
            // Don't convert numeric values like points, counts, etc.
            // Only convert if the old value was also a boolean or if it's a known boolean field
            if ((value === 1 || value === 0) && typeof value === 'number') {
                // Check if this looks like a boolean field (field name suggests boolean)
                // For now, we'll be more conservative and only convert if it's explicitly boolean
                // This prevents converting points, counts, etc.
                return String(value);
            }
            // Format date strings (ISO format: YYYY-MM-DDTHH:mm or YYYY-MM-DD HH:mm)
            const dateString = String(value);
            const datePattern = /^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}/;
            if (datePattern.test(dateString)) {
                try {
                    // Replace T with space for parsing if needed
                    const normalizedDate = dateString.replace('T', ' ');
                    const date = new Date(normalizedDate);
                    if (!isNaN(date.getTime())) {
                        return date.toLocaleString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: true,
                        });
                    }
                } catch {
                    // If parsing fails, return original value
                }
            }

            if (typeof value === 'object' && value !== null) {
                try {
                    const str = JSON.stringify(value);
                    // Limit length for very long objects
                    if (str.length > 100) {
                        return str.substring(0, 100) + '...';
                    }
                    return str;
                } catch {
                    return '[object]';
                }
            }
            return String(value);
        };

        Object.keys(attrs).forEach((key) => {
            const newValue = attrs[key];
            const oldValue = old[key];

            // Deep comparison for arrays and objects
            const valuesEqual = () => {
                if (Array.isArray(newValue) && Array.isArray(oldValue)) {
                    return (
                        JSON.stringify([...newValue].sort()) ===
                        JSON.stringify([...oldValue].sort())
                    );
                }
                if (
                    typeof newValue === 'object' &&
                    typeof oldValue === 'object' &&
                    newValue !== null &&
                    oldValue !== null
                ) {
                    return (
                        JSON.stringify(newValue) === JSON.stringify(oldValue)
                    );
                }
                return newValue === oldValue;
            };

            if (!valuesEqual()) {
                const formattedOld = formatValue(oldValue);
                const formattedNew = formatValue(newValue);

                if (oldValue !== undefined) {
                    changes.push(`${key}: ${formattedOld} → ${formattedNew}`);
                } else {
                    changes.push(`${key}: ${formattedNew}`);
                }
            }
        });

        return changes.length > 0 ? changes : null;
    };

    if (!entity) return null;

    const displayName = entity.full_name || entity.name;
    const displayEmail = entity.email;

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent className="overflow-hidden p-0 sm:max-w-3xl">
                <SheetHeader className="border-b p-6">
                    <div className="flex items-center gap-3">
                        <Activity className="h-5 w-5" />
                        <div className="flex-1">
                            <SheetTitle>{title}</SheetTitle>
                            <SheetDescription>
                                {description ||
                                    `Activity history for ${displayName}${
                                        displayEmail ? ` (${displayEmail})` : ''
                                    }`}
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
                                    This{' '}
                                    {title.toLowerCase().replace(' logs', '')}{' '}
                                    has no recorded activities yet.
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
