import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Calendar, Eye, Pencil, Plus, Trash2 } from 'lucide-react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';

interface Assignment {
    id: number;
    title: string;
    assignment_type: string;
    due_date: string | null;
    is_published: boolean;
    is_overdue: boolean;
    submissions_count: number;
    graded_count: number;
    created_by: string;
    created_at: string;
}

interface Props {
    assignments: Assignment[];
}

const assignmentTypeLabels: Record<string, string> = {
    case_report: 'Case Report',
    procedure_log: 'Procedure Log',
    journal_review: 'Journal Review',
    presentation: 'Presentation',
    research_paper: 'Research Paper',
    reflection: 'Reflection',
    other: 'Other',
};

export default function AssignmentsIndex({ assignments }: Props) {
    const handleDelete = (id: number) => {
        router.delete(`/assignments/${id}`, {
            onSuccess: () => {
                // Success toast handled by Inertia
            },
        });
    };

    return (
        <AppLayout>
            <Head title="Assignments" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold">Assignments</h1>
                        <p className="text-muted-foreground">
                            Create and manage assignments for residents
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/assignments/create">
                            <Plus className="mr-2 size-4" />
                            Create Assignment
                        </Link>
                    </Button>
                </div>

                {/* Assignments Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>All Assignments ({assignments.length})</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {assignments.length > 0 ? (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Title</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Due Date</TableHead>
                                        <TableHead className="text-center">
                                            Status
                                        </TableHead>
                                        <TableHead className="text-center">
                                            Submissions
                                        </TableHead>
                                        <TableHead className="text-center">
                                            Graded
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {assignments.map((assignment) => (
                                        <TableRow key={assignment.id}>
                                            <TableCell className="font-medium">
                                                {assignment.title}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">
                                                    {assignmentTypeLabels[assignment.assignment_type]}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {assignment.due_date ? (
                                                    <div className="flex items-center gap-1">
                                                        <Calendar className="size-3" />
                                                        {new Date(
                                                            assignment.due_date,
                                                        ).toLocaleDateString()}
                                                        {assignment.is_overdue && (
                                                            <Badge
                                                                variant="destructive"
                                                                className="ml-2"
                                                            >
                                                                Overdue
                                                            </Badge>
                                                        )}
                                                    </div>
                                                ) : (
                                                    'No deadline'
                                                )}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                <Badge
                                                    variant={
                                                        assignment.is_published
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {assignment.is_published
                                                        ? 'Published'
                                                        : 'Draft'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-center">
                                                {assignment.submissions_count}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                {assignment.graded_count}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={`/assignments/${assignment.id}`}
                                                        >
                                                            <Eye className="size-4" />
                                                        </Link>
                                                    </Button>
                                                    <AlertDialog>
                                                        <AlertDialogTrigger asChild>
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                            >
                                                                <Trash2 className="size-4 text-destructive" />
                                                            </Button>
                                                        </AlertDialogTrigger>
                                                        <AlertDialogContent>
                                                            <AlertDialogHeader>
                                                                <AlertDialogTitle>
                                                                    Delete Assignment?
                                                                </AlertDialogTitle>
                                                                <AlertDialogDescription>
                                                                    This will permanently delete the assignment
                                                                    and all submissions. This action cannot be undone.
                                                                </AlertDialogDescription>
                                                            </AlertDialogHeader>
                                                            <AlertDialogFooter>
                                                                <AlertDialogCancel>
                                                                    Cancel
                                                                </AlertDialogCancel>
                                                                <AlertDialogAction
                                                                    onClick={() =>
                                                                        handleDelete(
                                                                            assignment.id,
                                                                        )
                                                                    }
                                                                >
                                                                    Delete
                                                                </AlertDialogAction>
                                                            </AlertDialogFooter>
                                                        </AlertDialogContent>
                                                    </AlertDialog>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        ) : (
                            <div className="flex flex-col items-center justify-center py-12">
                                <Pencil className="mb-4 size-12 text-muted-foreground" />
                                <h3 className="mb-2 text-lg font-semibold">
                                    No assignments yet
                                </h3>
                                <p className="mb-4 text-center text-sm text-muted-foreground">
                                    Create your first assignment to get started
                                </p>
                                <Button asChild>
                                    <Link href="/assignments/create">
                                        <Plus className="mr-2 size-4" />
                                        Create Assignment
                                    </Link>
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

