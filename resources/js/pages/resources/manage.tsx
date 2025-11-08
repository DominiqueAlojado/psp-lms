import { DeleteConfirmationDialog } from '@/components/delete-confirmation-dialog';
import HeadingSmall from '@/components/heading-small';
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
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    BookOpen,
    Download,
    Edit,
    Plus,
    Search,
    Trash2,
    Upload,
    X,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Learning Resources',
        href: '/resources',
    },
    {
        title: 'Manage',
        href: '/resources/manage',
    },
];

const RESOURCE_CATEGORIES = [
    'Lecture Notes',
    'Study Guides',
    'Clinical Guidelines',
    'Video Tutorials',
    'Reference Materials',
    'Practice Cases',
    'Journal Articles',
    'Protocols & Procedures',
    'Handouts',
    'Other',
] as const;

const YEAR_LEVELS = ['PGY-1', 'PGY-2', 'PGY-3', 'PGY-4', 'PGY-5'];

interface Resource {
    id: number;
    title: string;
    description: string | null;
    category: string;
    file_name: string;
    file_type: string;
    file_size_formatted: string;
    file_url: string;
    target_year_levels: string[] | null;
    is_published: boolean;
    download_count: number;
    uploaded_by: string;
    created_at: string;
    updated_at: string;
}

interface PaginatedResources {
    data: Resource[];
    total: number;
    current_page: number;
    last_page: number;
}

interface PageProps {
    resources: PaginatedResources;
    categories: string[];
    filters: {
        search?: string;
        category?: string;
        is_published?: boolean;
    };
    [key: string]: unknown;
}

export default function ManageResources() {
    const { resources, categories, filters } = usePage<PageProps>().props;
    const [search, setSearch] = useState(filters.search || '');
    const [category, setCategory] = useState(filters.category || '');
    const [showUploadDialog, setShowUploadDialog] = useState(false);
    const [showEditDialog, setShowEditDialog] = useState(false);
    const [selectedResource, setSelectedResource] = useState<Resource | null>(
        null,
    );
    const [deletingResource, setDeletingResource] = useState<Resource | null>(
        null,
    );

    // Upload form state
    const [uploadTitle, setUploadTitle] = useState('');
    const [uploadDescription, setUploadDescription] = useState('');
    const [uploadCategory, setUploadCategory] = useState('');
    const [uploadYearLevels, setUploadYearLevels] = useState<string[]>([]);
    const [uploadFile, setUploadFile] = useState<File | null>(null);
    const [uploading, setUploading] = useState(false);

    // Edit form state
    const [editTitle, setEditTitle] = useState('');
    const [editDescription, setEditDescription] = useState('');
    const [editCategory, setEditCategory] = useState('');
    const [editYearLevels, setEditYearLevels] = useState<string[]>([]);
    const [editIsPublished, setEditIsPublished] = useState(true);
    const [updating, setUpdating] = useState(false);

    const handleSearch = () => {
        router.get(
            '/resources/manage',
            { search: search || undefined, category: category || undefined },
            { preserveState: true, preserveScroll: true },
        );
    };

    const clearFilters = () => {
        setSearch('');
        setCategory('');
        router.get('/resources/manage', {}, { preserveState: true });
    };

    const openUploadDialog = () => {
        setUploadTitle('');
        setUploadDescription('');
        setUploadCategory('');
        setUploadYearLevels([]);
        setUploadFile(null);
        setShowUploadDialog(true);
    };

    const handleUpload = (e: React.FormEvent) => {
        e.preventDefault();

        console.log('=== UPLOAD FORM SUBMITTED ===');
        console.log('Form data:', {
            title: uploadTitle,
            category: uploadCategory,
            file: uploadFile?.name,
            fileSize: uploadFile?.size,
            fileType: uploadFile?.type,
        });

        if (!uploadTitle || !uploadCategory || !uploadFile) {
            console.error('❌ VALIDATION FAILED', {
                hasTitle: !!uploadTitle,
                hasCategory: !!uploadCategory,
                hasFile: !!uploadFile,
            });
            toast.error('Please fill in required fields and select a file');
            return;
        }

        console.log('✅ Validation passed, preparing upload...');

        setUploading(true);

        const formData = new FormData();
        formData.append('title', uploadTitle);
        formData.append('description', uploadDescription);
        formData.append('category', uploadCategory);
        formData.append('file', uploadFile);
        formData.append('is_published', '1');

        if (uploadYearLevels.length > 0) {
            uploadYearLevels.forEach((level) => {
                formData.append('target_year_levels[]', level);
            });
        }

        console.log('📦 FormData prepared');

        // Log FormData contents
        console.log('FormData contents:');
        for (const [key, value] of formData.entries()) {
            if (value instanceof File) {
                console.log(
                    `  ${key}:`,
                    `File(${value.name}, ${value.size} bytes)`,
                );
            } else {
                console.log(`  ${key}:`, value);
            }
        }

        console.log('🚀 Calling router.post to /resources...');

        router.post('/resources', formData, {
            forceFormData: true,
            preserveScroll: true,
            onStart: (visit) => {
                console.log('✅ onStart - Request sent to server');
                console.log('Visit object:', visit);
            },
            onSuccess: (page) => {
                console.log('✅ onSuccess - Upload successful!');
                console.log('Page:', page);
                toast.success('Resource uploaded successfully!');
                setShowUploadDialog(false);
                // Reset form
                setUploadTitle('');
                setUploadDescription('');
                setUploadCategory('');
                setUploadYearLevels([]);
                setUploadFile(null);
            },
            onError: (errors) => {
                console.error('❌ onError - Upload failed');
                console.error('Errors:', errors);
                const errorMsg =
                    typeof errors === 'object'
                        ? Object.values(errors).flat().join(', ')
                        : 'Failed to upload resource';
                toast.error(errorMsg);
            },
            onFinish: () => {
                console.log('⚡ onFinish - Request completed');
                setUploading(false);
            },
        });

        console.log('📝 router.post() called, waiting for response...');
    };

    const openEditDialog = (resource: Resource) => {
        setSelectedResource(resource);
        setEditTitle(resource.title);
        setEditDescription(resource.description || '');
        setEditCategory(resource.category);
        setEditYearLevels(resource.target_year_levels || []);
        setEditIsPublished(resource.is_published);
        setShowEditDialog(true);
    };

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();

        if (!selectedResource || !editTitle || !editCategory) {
            toast.error('Please fill in required fields');
            return;
        }

        setUpdating(true);

        router.patch(
            `/resources/${selectedResource.id}`,
            {
                title: editTitle,
                description: editDescription,
                category: editCategory,
                target_year_levels:
                    editYearLevels.length > 0 ? editYearLevels : null,
                is_published: editIsPublished,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Resource updated successfully!');
                    setShowEditDialog(false);
                },
                onError: (errors) => {
                    console.error('Update error:', errors);
                    toast.error('Failed to update resource');
                },
                onFinish: () => setUpdating(false),
            },
        );
    };

    const confirmDelete = () => {
        if (!deletingResource) return;

        router.delete(`/resources/${deletingResource.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Resource deleted successfully!');
            },
            onFinish: () => setDeletingResource(null),
        });
    };

    const toggleYearLevel = (level: string, isUpload: boolean) => {
        if (isUpload) {
            setUploadYearLevels((prev) =>
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

    const hasActiveFilters = filters.search || filters.category;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Manage Resources" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex items-start justify-between gap-4">
                    <HeadingSmall
                        title="Manage Learning Resources"
                        description="Upload and manage study materials for residents"
                    />
                    <Button onClick={openUploadDialog}>
                        <Plus className="mr-2 h-4 w-4" />
                        Upload Resource
                    </Button>
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="p-4">
                        <div className="flex flex-col gap-3 sm:flex-row">
                            <div className="relative flex-1">
                                <Search className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                <Input
                                    placeholder="Search resources..."
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
                                value={category || undefined}
                                onValueChange={(value) => setCategory(value)}
                            >
                                <SelectTrigger className="w-full sm:w-[200px]">
                                    <SelectValue placeholder="All Categories" />
                                </SelectTrigger>
                                <SelectContent>
                                    {categories.map((cat) => (
                                        <SelectItem key={cat} value={cat}>
                                            {cat}
                                        </SelectItem>
                                    ))}
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

                {/* Resources List */}
                {resources.data.length === 0 ? (
                    <Card>
                        <CardContent className="p-12 text-center">
                            <BookOpen className="mx-auto h-12 w-12 text-muted-foreground" />
                            <p className="mt-4 text-sm text-muted-foreground">
                                {hasActiveFilters
                                    ? 'No resources found matching your search'
                                    : 'No resources uploaded yet'}
                            </p>
                            <Button onClick={openUploadDialog} className="mt-4">
                                <Plus className="mr-2 h-4 w-4" />
                                Upload First Resource
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="space-y-3">
                        {resources.data.map((resource) => (
                            <Card key={resource.id}>
                                <CardContent className="p-4">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="flex-1">
                                            <div className="flex items-center gap-2">
                                                <h3 className="font-semibold">
                                                    {resource.title}
                                                </h3>
                                                <Badge variant="secondary">
                                                    {resource.category}
                                                </Badge>
                                                {!resource.is_published && (
                                                    <Badge variant="outline">
                                                        Unpublished
                                                    </Badge>
                                                )}
                                            </div>
                                            {resource.description && (
                                                <p className="mt-1 text-sm text-muted-foreground">
                                                    {resource.description}
                                                </p>
                                            )}
                                            <div className="mt-2 flex flex-wrap gap-3 text-xs text-muted-foreground">
                                                <span>
                                                    {resource.file_name} (
                                                    {resource.file_type.toUpperCase()}
                                                    )
                                                </span>
                                                <span>•</span>
                                                <span>
                                                    {
                                                        resource.file_size_formatted
                                                    }
                                                </span>
                                                <span>•</span>
                                                <span>
                                                    {resource.download_count}{' '}
                                                    downloads
                                                </span>
                                                <span>•</span>
                                                <span>
                                                    Uploaded{' '}
                                                    {resource.updated_at}
                                                </span>
                                            </div>
                                        </div>
                                        <div className="flex gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    openEditDialog(resource)
                                                }
                                            >
                                                <Edit className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={`/resources/${resource.id}/download`}
                                                    as="a"
                                                    target="_blank"
                                                >
                                                    <Download className="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    setDeletingResource(
                                                        resource,
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

                {/* Upload Dialog */}
                <Dialog
                    open={showUploadDialog}
                    onOpenChange={setShowUploadDialog}
                >
                    <DialogContent className="max-h-[90vh] w-[95vw] max-w-2xl overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>Upload Learning Resource</DialogTitle>
                            <DialogDescription>
                                Add study materials, notes, or other resources
                                for residents
                            </DialogDescription>
                        </DialogHeader>
                        <form onSubmit={handleUpload} className="space-y-4">
                            <div className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="title">
                                        Title{' '}
                                        <span className="text-destructive">
                                            *
                                        </span>
                                    </Label>
                                    <Input
                                        id="title"
                                        value={uploadTitle}
                                        onChange={(e) =>
                                            setUploadTitle(e.target.value)
                                        }
                                        placeholder="e.g., Pharmacology Study Guide"
                                        required
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="description">
                                        Description
                                    </Label>
                                    <Textarea
                                        id="description"
                                        value={uploadDescription}
                                        onChange={(e) =>
                                            setUploadDescription(e.target.value)
                                        }
                                        placeholder="Brief description of the resource"
                                        rows={3}
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="category">
                                        Category{' '}
                                        <span className="text-destructive">
                                            *
                                        </span>
                                    </Label>
                                    <Select
                                        value={uploadCategory}
                                        onValueChange={setUploadCategory}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select category" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {RESOURCE_CATEGORIES.map((cat) => (
                                                <SelectItem
                                                    key={cat}
                                                    value={cat}
                                                >
                                                    {cat}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="file">
                                        File{' '}
                                        <span className="text-destructive">
                                            *
                                        </span>
                                    </Label>
                                    <Input
                                        id="file"
                                        type="file"
                                        onChange={(e) =>
                                            setUploadFile(
                                                e.target.files?.[0] || null,
                                            )
                                        }
                                        required
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        Max file size: 50MB. Supported formats:
                                        PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX,
                                        MP4, etc.
                                    </p>
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
                                                    id={`upload-${level}`}
                                                    checked={uploadYearLevels.includes(
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
                                                    htmlFor={`upload-${level}`}
                                                    className="cursor-pointer text-sm"
                                                >
                                                    {level}
                                                </Label>
                                            </div>
                                        ))}
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        Leave empty to make available for all
                                        residents
                                    </p>
                                </div>
                            </div>

                            <DialogFooter className="mt-6 flex-col gap-2 sm:flex-row">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setShowUploadDialog(false)}
                                    disabled={uploading}
                                    className="w-full sm:w-auto"
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={uploading}
                                    className="w-full sm:w-auto"
                                >
                                    <Upload className="mr-2 h-4 w-4" />
                                    {uploading ? 'Uploading...' : 'Upload'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>

                {/* Edit Dialog */}
                {selectedResource && (
                    <Dialog
                        open={showEditDialog}
                        onOpenChange={setShowEditDialog}
                    >
                        <DialogContent className="max-h-[90vh] w-[95vw] max-w-2xl overflow-y-auto">
                            <DialogHeader>
                                <DialogTitle>Edit Resource</DialogTitle>
                                <DialogDescription>
                                    Update resource details and settings
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
                                        <Label htmlFor="edit-description">
                                            Description
                                        </Label>
                                        <Textarea
                                            id="edit-description"
                                            value={editDescription}
                                            onChange={(e) =>
                                                setEditDescription(
                                                    e.target.value,
                                                )
                                            }
                                            rows={3}
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="edit-category">
                                            Category{' '}
                                            <span className="text-destructive">
                                                *
                                            </span>
                                        </Label>
                                        <Select
                                            value={editCategory}
                                            onValueChange={setEditCategory}
                                            required
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select category" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {RESOURCE_CATEGORIES.map(
                                                    (cat) => (
                                                        <SelectItem
                                                            key={cat}
                                                            value={cat}
                                                        >
                                                            {cat}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
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

                                    <div className="flex items-center gap-2">
                                        <Checkbox
                                            id="is-published"
                                            checked={editIsPublished}
                                            onCheckedChange={(checked) =>
                                                setEditIsPublished(
                                                    checked as boolean,
                                                )
                                            }
                                        />
                                        <Label
                                            htmlFor="is-published"
                                            className="cursor-pointer text-sm"
                                        >
                                            Published (visible to residents)
                                        </Label>
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
                                            : 'Update Resource'}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                )}

                {/* Delete Confirmation */}
                {deletingResource && (
                    <DeleteConfirmationDialog
                        open={deletingResource !== null}
                        onConfirm={confirmDelete}
                        onCancel={() => setDeletingResource(null)}
                        title="Delete Resource?"
                        itemIdentifier={deletingResource.title}
                        itemName={deletingResource.file_name}
                        warningMessage="This will permanently delete the resource file and all its data. This action cannot be undone."
                        confirmText="Delete Resource"
                    />
                )}
            </div>
        </AppLayout>
    );
}
