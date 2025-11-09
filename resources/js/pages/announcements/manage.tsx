import { DeleteConfirmationDialog } from '@/components/delete-confirmation-dialog';
import HeadingSmall from '@/components/heading-small';
import { RichTextEditor } from '@/components/rich-text-editor';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import {
    AlertCircle,
    Edit,
    Eye,
    Megaphone,
    Pin,
    Plus,
    Search,
    Trash2,
    X,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Announcements',
        href: '/announcements',
    },
    {
        title: 'Manage',
        href: '/announcements/manage',
    },
];

const YEAR_LEVELS = ['PGY-1', 'PGY-2', 'PGY-3', 'PGY-4', 'PGY-5'];

interface Announcement {
    id: number;
    title: string;
    content: string;
    scope: 'organization' | 'system';
    priority: 'normal' | 'important' | 'urgent';
    is_published: boolean;
    is_pinned: boolean;
    target_year_levels: string[] | null;
    expires_at: string | null;
    organization_name: string | null;
    created_by: string;
    created_at: string;
    updated_at: string;
    views_count: number;
}

interface PaginatedAnnouncements {
    data: Announcement[];
    total: number;
    current_page: number;
    last_page: number;
}

interface PageProps {
    announcements: PaginatedAnnouncements;
    filters: {
        search?: string;
        scope?: string;
        is_published?: boolean;
    };
    canCreateSystem: boolean;
    [key: string]: unknown;
}

export default function ManageAnnouncements() {
    const { announcements, filters, canCreateSystem } =
        usePage<PageProps>().props;
    const [search, setSearch] = useState(filters.search || '');
    const [scopeFilter, setScopeFilter] = useState(filters.scope || '');
    const [showCreateDialog, setShowCreateDialog] = useState(false);
    const [showEditDialog, setShowEditDialog] = useState(false);
    const [selectedAnnouncement, setSelectedAnnouncement] =
        useState<Announcement | null>(null);
    const [deletingAnnouncement, setDeletingAnnouncement] =
        useState<Announcement | null>(null);

    // Create form state
    const [createTitle, setCreateTitle] = useState('');
    const [createContent, setCreateContent] = useState('');
    const [createScope, setCreateScope] = useState<'organization' | 'system'>(
        'organization',
    );
    const [createPriority, setCreatePriority] = useState<
        'normal' | 'important' | 'urgent'
    >('normal');
    const [createIsPinned, setCreateIsPinned] = useState(false);
    const [createYearLevels, setCreateYearLevels] = useState<string[]>([]);
    const [createExpiresAt, setCreateExpiresAt] = useState('');
    const [creating, setCreating] = useState(false);

    // Edit form state
    const [editTitle, setEditTitle] = useState('');
    const [editContent, setEditContent] = useState('');
    const [editScope, setEditScope] = useState<'organization' | 'system'>(
        'organization',
    );
    const [editPriority, setEditPriority] = useState<
        'normal' | 'important' | 'urgent'
    >('normal');
    const [editIsPublished, setEditIsPublished] = useState(true);
    const [editIsPinned, setEditIsPinned] = useState(false);
    const [editYearLevels, setEditYearLevels] = useState<string[]>([]);
    const [editExpiresAt, setEditExpiresAt] = useState('');
    const [updating, setUpdating] = useState(false);

    const handleSearch = () => {
        router.get(
            '/announcements/manage',
            {
                search: search || undefined,
                scope: scopeFilter || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const clearFilters = () => {
        setSearch('');
        setScopeFilter('');
        router.get('/announcements/manage', {}, { preserveState: true });
    };

    const openCreateDialog = () => {
        setCreateTitle('');
        setCreateContent('');
        setCreateScope('organization');
        setCreatePriority('normal');
        setCreateIsPinned(false);
        setCreateYearLevels([]);
        setCreateExpiresAt('');
        setShowCreateDialog(true);
    };

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();

        if (!createTitle || !createContent) {
            toast.error('Please fill in required fields');
            return;
        }

        setCreating(true);

        router.post(
            '/announcements',
            {
                title: createTitle,
                content: createContent,
                scope: createScope,
                priority: createPriority,
                is_published: true,
                is_pinned: createIsPinned,
                target_year_levels:
                    createYearLevels.length > 0 ? createYearLevels : null,
                expires_at: createExpiresAt || null,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Announcement created successfully!');
                    setShowCreateDialog(false);
                },
                onError: (errors) => {
                    console.error('Create error:', errors);
                    const errorMsg =
                        typeof errors === 'object'
                            ? Object.values(errors).flat().join(', ')
                            : 'Failed to create announcement';
                    toast.error(errorMsg);
                },
                onFinish: () => setCreating(false),
            },
        );
    };

    const openEditDialog = (announcement: Announcement) => {
        setSelectedAnnouncement(announcement);
        setEditTitle(announcement.title);
        setEditContent(announcement.content);
        setEditScope(announcement.scope);
        setEditPriority(announcement.priority);
        setEditIsPublished(announcement.is_published);
        setEditIsPinned(announcement.is_pinned);
        setEditYearLevels(announcement.target_year_levels || []);
        setEditExpiresAt(announcement.expires_at || '');
        setShowEditDialog(true);
    };

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();

        if (!selectedAnnouncement || !editTitle || !editContent) {
            toast.error('Please fill in required fields');
            return;
        }

        setUpdating(true);

        router.patch(
            `/announcements/${selectedAnnouncement.id}`,
            {
                title: editTitle,
                content: editContent,
                scope: editScope,
                priority: editPriority,
                is_published: editIsPublished,
                is_pinned: editIsPinned,
                target_year_levels:
                    editYearLevels.length > 0 ? editYearLevels : null,
                expires_at: editExpiresAt || null,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Announcement updated successfully!');
                    setShowEditDialog(false);
                },
                onError: (errors) => {
                    console.error('Update error:', errors);
                    toast.error('Failed to update announcement');
                },
                onFinish: () => setUpdating(false),
            },
        );
    };

    const confirmDelete = () => {
        if (!deletingAnnouncement) return;

        router.delete(`/announcements/${deletingAnnouncement.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Announcement deleted successfully!');
            },
            onFinish: () => setDeletingAnnouncement(null),
        });
    };

    const toggleYearLevel = (level: string, isCreate: boolean) => {
        if (isCreate) {
            setCreateYearLevels((prev) =>
                prev.includes(level)
                    ? prev.filter((l) => l !== level)
                    : [...prev, level],
            );
        } else {
            setEditYearLevels((prev) =>
                prev.includes(level)
                    ? prev.filter((l) => l !== level)
                    : [...prev, level],
            );
        }
    };

    const hasActiveFilters = filters.search || filters.scope;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Manage Announcements" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex items-start justify-between gap-4">
                    <HeadingSmall
                        title="Manage Announcements"
                        description="Create and manage announcements for residents"
                    />
                    <Button onClick={openCreateDialog}>
                        <Plus className="mr-2 h-4 w-4" />
                        Create Announcement
                    </Button>
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="p-4">
                        <div className="flex flex-col gap-3 sm:flex-row">
                            <div className="relative flex-1">
                                <Search className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                <Input
                                    placeholder="Search announcements..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter') {
                                            handleSearch();
                                        }
                                    }}
                                    className="pl-9"
                                />
                            </div>
                            <Select
                                value={scopeFilter || undefined}
                                onValueChange={(value) =>
                                    setScopeFilter(value)
                                }
                            >
                                <SelectTrigger className="w-full sm:w-[200px]">
                                    <SelectValue placeholder="All Scopes" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="organization">
                                        Organization
                                    </SelectItem>
                                    <SelectItem value="system">
                                        System-wide
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <div className="flex gap-2">
                                <Button onClick={handleSearch}>Search</Button>
                                {hasActiveFilters && (
                                    <Button
                                        variant="outline"
                                        onClick={clearFilters}
                                    >
                                        <X className="mr-2 h-4 w-4" />
                                        Clear
                                    </Button>
                                )}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Announcements List */}
                {announcements.data.length === 0 ? (
                    <Card>
                        <CardContent className="p-12 text-center">
                            <Megaphone className="mx-auto h-12 w-12 text-muted-foreground" />
                            <p className="mt-4 text-sm text-muted-foreground">
                                {hasActiveFilters
                                    ? 'No announcements found matching your search'
                                    : 'No announcements created yet'}
                            </p>
                            <Button onClick={openCreateDialog} className="mt-4">
                                <Plus className="mr-2 h-4 w-4" />
                                Create First Announcement
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="space-y-3">
                        {announcements.data.map((announcement) => (
                            <Card key={announcement.id}>
                                <CardContent className="p-4">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="flex-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h3 className="font-semibold">
                                                    {announcement.title}
                                                </h3>
                                                {announcement.is_pinned && (
                                                    <Badge
                                                        variant="secondary"
                                                        className="gap-1"
                                                    >
                                                        <Pin className="h-3 w-3" />
                                                        Pinned
                                                    </Badge>
                                                )}
                                                {announcement.priority !==
                                                    'normal' && (
                                                    <Badge
                                                        variant={
                                                            announcement.priority ===
                                                            'urgent'
                                                                ? 'destructive'
                                                                : 'default'
                                                        }
                                                    >
                                                        {announcement.priority.toUpperCase()}
                                                    </Badge>
                                                )}
                                                {announcement.scope ===
                                                    'system' && (
                                                    <Badge variant="outline">
                                                        System-wide
                                                    </Badge>
                                                )}
                                                {!announcement.is_published && (
                                                    <Badge variant="outline">
                                                        Unpublished
                                                    </Badge>
                                                )}
                                            </div>
                                            <p className="mt-1 line-clamp-2 text-sm text-muted-foreground">
                                                {announcement.content.replace(
                                                    /<[^>]*>/g,
                                                    '',
                                                )}
                                            </p>
                                            <div className="mt-2 flex flex-wrap gap-3 text-xs text-muted-foreground">
                                                <span>
                                                    Posted by{' '}
                                                    {announcement.created_by}
                                                </span>
                                                <span>•</span>
                                                <span>
                                                    Updated{' '}
                                                    {announcement.updated_at}
                                                </span>
                                                <span>•</span>
                                                <span className="flex items-center gap-1">
                                                    <Eye className="h-3 w-3" />
                                                    {announcement.views_count}{' '}
                                                    {announcement.views_count ===
                                                    1
                                                        ? 'view'
                                                        : 'views'}
                                                </span>
                                                {announcement.expires_at && (
                                                    <>
                                                        <span>•</span>
                                                        <span>
                                                            Expires{' '}
                                                            {
                                                                announcement.expires_at
                                                            }
                                                        </span>
                                                    </>
                                                )}
                                            </div>
                                        </div>
                                        <div className="flex gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    openEditDialog(announcement)
                                                }
                                            >
                                                <Edit className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    setDeletingAnnouncement(
                                                        announcement,
                                                    )
                                                }
                                            >
                                                <Trash2 className="h-4 w-4 text-destructive" />
                                            </Button>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                {/* Create Dialog */}
                <Dialog
                    open={showCreateDialog}
                    onOpenChange={setShowCreateDialog}
                >
                    <DialogContent className="max-h-[90vh] w-[95vw] max-w-2xl overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>Create Announcement</DialogTitle>
                            <DialogDescription>
                                Share important updates with residents
                            </DialogDescription>
                        </DialogHeader>
                        <form onSubmit={handleCreate} className="space-y-4">
                            <div className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="create-title">
                                        Title{' '}
                                        <span className="text-destructive">
                                            *
                                        </span>
                                    </Label>
                                    <Input
                                        id="create-title"
                                        value={createTitle}
                                        onChange={(e) =>
                                            setCreateTitle(e.target.value)
                                        }
                                        placeholder="e.g., New Rotation Schedule"
                                        required
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="create-content">
                                        Content{' '}
                                        <span className="text-destructive">
                                            *
                                        </span>
                                    </Label>
                                    <RichTextEditor
                                        value={createContent}
                                        onChange={setCreateContent}
                                        placeholder="Enter the announcement details..."
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        Use the toolbar to format your
                                        announcement with bold, italic, lists,
                                        links, and more.
                                    </p>
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="create-scope">
                                            Visibility Scope{' '}
                                            <span className="text-destructive">
                                                *
                                            </span>
                                        </Label>
                                        <Select
                                            value={createScope}
                                            onValueChange={(value) =>
                                                setCreateScope(
                                                    value as
                                                        | 'organization'
                                                        | 'system',
                                                )
                                            }
                                            disabled={!canCreateSystem}
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="organization">
                                                    My Organization Only
                                                </SelectItem>
                                                {canCreateSystem && (
                                                    <SelectItem value="system">
                                                        System-wide (All
                                                        Organizations)
                                                    </SelectItem>
                                                )}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="create-priority">
                                            Priority{' '}
                                            <span className="text-destructive">
                                                *
                                            </span>
                                        </Label>
                                        <Select
                                            value={createPriority}
                                            onValueChange={(value) =>
                                                setCreatePriority(
                                                    value as
                                                        | 'normal'
                                                        | 'important'
                                                        | 'urgent',
                                                )
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="normal">
                                                    Normal
                                                </SelectItem>
                                                <SelectItem value="important">
                                                    Important
                                                </SelectItem>
                                                <SelectItem value="urgent">
                                                    Urgent
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="create-expires">
                                        Expiration Date (Optional)
                                    </Label>
                                    <Input
                                        id="create-expires"
                                        type="date"
                                        value={createExpiresAt}
                                        onChange={(e) =>
                                            setCreateExpiresAt(e.target.value)
                                        }
                                        min={
                                            new Date(
                                                Date.now() + 86400000,
                                            )
                                                .toISOString()
                                                .split('T')[0]
                                        }
                                    />
                                </div>

                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="create-pinned"
                                        checked={createIsPinned}
                                        onCheckedChange={(checked) =>
                                            setCreateIsPinned(
                                                checked as boolean,
                                            )
                                        }
                                    />
                                    <Label
                                        htmlFor="create-pinned"
                                        className="cursor-pointer text-sm"
                                    >
                                        Pin to top
                                    </Label>
                                </div>

                                <div className="space-y-2">
                                    <Label>Target Year Levels (Optional)</Label>
                                    <div className="grid grid-cols-2 gap-3 sm:flex sm:flex-wrap">
                                        {YEAR_LEVELS.map((level) => (
                                            <div
                                                key={level}
                                                className="flex items-center gap-2"
                                            >
                                                <Checkbox
                                                    id={`create-${level}`}
                                                    checked={createYearLevels.includes(
                                                        level,
                                                    )}
                                                    onCheckedChange={() =>
                                                        toggleYearLevel(
                                                            level,
                                                            true,
                                                        )
                                                    }
                                                />
                                                <Label
                                                    htmlFor={`create-${level}`}
                                                    className="cursor-pointer text-sm"
                                                >
                                                    {level}
                                                </Label>
                                            </div>
                                        ))}
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        Leave empty to show to all residents
                                    </p>
                                </div>
                            </div>

                            <DialogFooter className="mt-6 flex-col gap-2 sm:flex-row">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setShowCreateDialog(false)}
                                    disabled={creating}
                                    className="w-full sm:w-auto"
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={creating}
                                    className="w-full sm:w-auto"
                                >
                                    <Megaphone className="mr-2 h-4 w-4" />
                                    {creating
                                        ? 'Creating...'
                                        : 'Create Announcement'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>

                {/* Edit Dialog */}
                {selectedAnnouncement && (
                    <Dialog
                        open={showEditDialog}
                        onOpenChange={setShowEditDialog}
                    >
                        <DialogContent className="max-h-[90vh] w-[95vw] max-w-2xl overflow-y-auto">
                            <DialogHeader>
                                <DialogTitle>Edit Announcement</DialogTitle>
                                <DialogDescription>
                                    Update announcement details
                                </DialogDescription>
                            </DialogHeader>
                            <form onSubmit={handleUpdate} className="space-y-4">
                                <div className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="edit-title">
                                            Title{' '}
                                            <span className="text-destructive">
                                                *
                                            </span>
                                        </Label>
                                        <Input
                                            id="edit-title"
                                            value={editTitle}
                                            onChange={(e) =>
                                                setEditTitle(e.target.value)
                                            }
                                            required
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="edit-content">
                                            Content{' '}
                                            <span className="text-destructive">
                                                *
                                            </span>
                                        </Label>
                                        <RichTextEditor
                                            value={editContent}
                                            onChange={setEditContent}
                                            placeholder="Enter the announcement details..."
                                        />
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="edit-scope">
                                                Visibility Scope{' '}
                                                <span className="text-destructive">
                                                    *
                                                </span>
                                            </Label>
                                            <Select
                                                value={editScope}
                                                onValueChange={(value) =>
                                                    setEditScope(
                                                        value as
                                                            | 'organization'
                                                            | 'system',
                                                    )
                                                }
                                                disabled={!canCreateSystem}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="organization">
                                                        My Organization Only
                                                    </SelectItem>
                                                    {canCreateSystem && (
                                                        <SelectItem value="system">
                                                            System-wide (All
                                                            Organizations)
                                                        </SelectItem>
                                                    )}
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="edit-priority">
                                                Priority{' '}
                                                <span className="text-destructive">
                                                    *
                                                </span>
                                            </Label>
                                            <Select
                                                value={editPriority}
                                                onValueChange={(value) =>
                                                    setEditPriority(
                                                        value as
                                                            | 'normal'
                                                            | 'important'
                                                            | 'urgent',
                                                    )
                                                }
                                            >
                                                <SelectTrigger>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="normal">
                                                        Normal
                                                    </SelectItem>
                                                    <SelectItem value="important">
                                                        Important
                                                    </SelectItem>
                                                    <SelectItem value="urgent">
                                                        Urgent
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="edit-expires">
                                            Expiration Date (Optional)
                                        </Label>
                                        <Input
                                            id="edit-expires"
                                            type="date"
                                            value={editExpiresAt}
                                            onChange={(e) =>
                                                setEditExpiresAt(e.target.value)
                                            }
                                            min={
                                                new Date(
                                                    Date.now() + 86400000,
                                                )
                                                    .toISOString()
                                                    .split('T')[0]
                                            }
                                        />
                                    </div>

                                    <div className="flex flex-col gap-2">
                                        <div className="flex items-center gap-2">
                                            <Checkbox
                                                id="edit-published"
                                                checked={editIsPublished}
                                                onCheckedChange={(checked) =>
                                                    setEditIsPublished(
                                                        checked as boolean,
                                                    )
                                                }
                                            />
                                            <Label
                                                htmlFor="edit-published"
                                                className="cursor-pointer text-sm"
                                            >
                                                Published (visible to residents)
                                            </Label>
                                        </div>

                                        <div className="flex items-center gap-2">
                                            <Checkbox
                                                id="edit-pinned"
                                                checked={editIsPinned}
                                                onCheckedChange={(checked) =>
                                                    setEditIsPinned(
                                                        checked as boolean,
                                                    )
                                                }
                                            />
                                            <Label
                                                htmlFor="edit-pinned"
                                                className="cursor-pointer text-sm"
                                            >
                                                Pin to top
                                            </Label>
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <Label>
                                            Target Year Levels (Optional)
                                        </Label>
                                        <div className="grid grid-cols-2 gap-3 sm:flex sm:flex-wrap">
                                            {YEAR_LEVELS.map((level) => (
                                                <div
                                                    key={level}
                                                    className="flex items-center gap-2"
                                                >
                                                    <Checkbox
                                                        id={`edit-${level}`}
                                                        checked={editYearLevels.includes(
                                                            level,
                                                        )}
                                                        onCheckedChange={() =>
                                                            toggleYearLevel(
                                                                level,
                                                                false,
                                                            )
                                                        }
                                                    />
                                                    <Label
                                                        htmlFor={`edit-${level}`}
                                                        className="cursor-pointer text-sm"
                                                    >
                                                        {level}
                                                    </Label>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>

                                <DialogFooter className="mt-6 flex-col gap-2 sm:flex-row">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => setShowEditDialog(false)}
                                        disabled={updating}
                                        className="w-full sm:w-auto"
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        type="submit"
                                        disabled={updating}
                                        className="w-full sm:w-auto"
                                    >
                                        {updating
                                            ? 'Updating...'
                                            : 'Update Announcement'}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                )}

                {/* Delete Confirmation */}
                {deletingAnnouncement && (
                    <DeleteConfirmationDialog
                        open={deletingAnnouncement !== null}
                        onConfirm={confirmDelete}
                        onCancel={() => setDeletingAnnouncement(null)}
                        title="Delete Announcement?"
                        itemIdentifier={deletingAnnouncement.title}
                        itemName={`announcement`}
                        warningMessage="This will permanently delete the announcement. This action cannot be undone."
                        confirmText="Delete Announcement"
                    />
                )}
            </div>
        </AppLayout>
    );
}

