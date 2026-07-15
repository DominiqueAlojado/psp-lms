import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { usePermissions } from '@/hooks/use-permissions';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    BookOpen,
    Download,
    FileText,
    Search,
    Settings,
    X,
} from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Learning Resources',
        href: '/resources',
    },
];

interface Resource {
    id: number;
    title: string;
    description: string | null;
    category: string;
    file_name: string;
    file_type: string;
    file_size_formatted: string;
    target_year_levels: string[] | null;
    download_count: number;
    uploaded_by: string;
    created_at: string;
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
    };
    [key: string]: unknown;
}

export default function ResourcesIndex() {
    const { resources, categories, filters } = usePage<PageProps>().props;
    const { hasPermission } = usePermissions();
    const [search, setSearch] = useState(filters.search || '');
    const [category, setCategory] = useState(filters.category || '');

    const handleSearch = () => {
        router.get(
            '/resources',
            { search: search || undefined, category: category || undefined },
            { preserveState: true, preserveScroll: true },
        );
    };

    const clearFilters = () => {
        setSearch('');
        setCategory('');
        router.get('/resources', {}, { preserveState: true });
    };

    const hasActiveFilters = filters.search || filters.category;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Learning Resources" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex items-start justify-between gap-4">
                    <HeadingSmall
                        title="Learning Resources"
                        description="Study materials, notes, and references for your training"
                    />
                    {hasPermission('upload-materials') && (
                        <Button asChild variant="outline">
                            <Link href="/resources/manage">
                                <Settings className="mr-2 h-4 w-4" />
                                Manage Resources
                            </Link>
                        </Button>
                    )}
                </div>

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

                {resources.data.length === 0 ? (
                    <Card>
                        <CardContent className="p-12 text-center">
                            <BookOpen className="mx-auto h-12 w-12 text-muted-foreground" />
                            <p className="mt-4 text-sm text-muted-foreground">
                                {hasActiveFilters
                                    ? 'No resources found matching your search'
                                    : 'No learning resources available yet'}
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {resources.data.map((resource) => (
                            <Card
                                key={resource.id}
                                className="transition-shadow hover:shadow-md"
                            >
                                <CardContent className="p-6">
                                    <div className="space-y-3">
                                        <div className="flex items-start justify-between gap-2">
                                            <div className="flex-1">
                                                <h3 className="line-clamp-2 font-semibold">
                                                    {resource.title}
                                                </h3>
                                            </div>
                                            <Badge
                                                variant="secondary"
                                                className="shrink-0"
                                            >
                                                {resource.category}
                                            </Badge>
                                        </div>

                                        {resource.description && (
                                            <p className="line-clamp-2 text-sm text-muted-foreground">
                                                {resource.description}
                                            </p>
                                        )}

                                        <div className="flex flex-wrap gap-2 text-xs text-muted-foreground">
                                            <div className="flex items-center gap-1">
                                                <FileText className="h-3 w-3" />
                                                {resource.file_type.toUpperCase()}
                                            </div>
                                            <span>-</span>
                                            <span>
                                                {resource.file_size_formatted}
                                            </span>
                                            <span>-</span>
                                            <div className="flex items-center gap-1">
                                                <Download className="h-3 w-3" />
                                                {resource.download_count}
                                            </div>
                                        </div>

                                        {resource.target_year_levels &&
                                            resource.target_year_levels.length >
                                                0 && (
                                                <div className="flex flex-wrap gap-1">
                                                    {resource.target_year_levels
                                                        .filter(
                                                            (level) =>
                                                                !level.startsWith(
                                                                    'PGY-',
                                                                ),
                                                        )
                                                        .map((level) => (
                                                            <Badge
                                                                key={level}
                                                                variant="outline"
                                                                className="text-xs"
                                                            >
                                                                {level}
                                                            </Badge>
                                                        ))}
                                                </div>
                                            )}

                                        <div className="pt-2">
                                            <Button
                                                asChild
                                                className="w-full"
                                                size="sm"
                                            >
                                                <a
                                                    href={`/resources/${resource.id}/download`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                >
                                                    <Download className="mr-2 h-4 w-4" />
                                                    Download
                                                </a>
                                            </Button>
                                        </div>

                                        <div className="text-xs text-muted-foreground">
                                            Uploaded by {resource.uploaded_by} -{' '}
                                            {resource.created_at}
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
