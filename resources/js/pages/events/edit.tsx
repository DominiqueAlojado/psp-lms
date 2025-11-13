import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface Event {
    id: number;
    title: string;
    description: string | null;
    event_category: string;
    event_type: string;
    start_date: string;
    end_date: string;
    registration_deadline: string | null;
    location: string | null;
    virtual_link: string | null;
    capacity: number | null;
    cme_credits: string | null;
    requirements: string | null;
    requires_approval: boolean;
    is_published: boolean;
}

interface PageProps {
    event: Event;
}

export default function EditEvent({ event }: PageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Events', href: '/events' },
        { title: 'Manage', href: '/events/manage' },
        { title: 'Edit Event', href: `/events/${event.id}/edit` },
    ];

    const { data, setData, patch, processing, errors } = useForm({
        title: event.title || '',
        description: event.description || '',
        event_category: event.event_category || 'other',
        event_type: event.event_type || 'in-person',
        start_date: event.start_date?.slice(0, 16) || '',
        end_date: event.end_date?.slice(0, 16) || '',
        registration_deadline: event.registration_deadline?.slice(0, 16) || '',
        location: event.location || '',
        virtual_link: event.virtual_link || '',
        capacity: event.capacity?.toString() || '',
        cme_credits: event.cme_credits || '',
        requirements: event.requirements || '',
        requires_approval: event.requires_approval || false,
        is_published: event.is_published || false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        patch(`/events/${event.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${event.title}`} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex items-start justify-between gap-4">
                    <HeadingSmall
                        title="Edit Event"
                        description="Update event details"
                    />
                    <div className="flex gap-2">
                        <Button asChild variant="outline">
                            <Link href={`/events/${event.id}/attendees`}>View Attendees</Link>
                        </Button>
                        <Button asChild variant="outline">
                            <Link href="/events/manage">Cancel</Link>
                        </Button>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Basic Information</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="title">Event Title *</Label>
                                <Input
                                    id="title"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    required
                                />
                                {errors.title && <p className="text-sm text-destructive">{errors.title}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    rows={4}
                                />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Category *</Label>
                                    <Select value={data.event_category} onValueChange={(value) => setData('event_category', value)}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="convention">Convention</SelectItem>
                                            <SelectItem value="workshop">Workshop</SelectItem>
                                            <SelectItem value="seminar">Seminar</SelectItem>
                                            <SelectItem value="cme">CME</SelectItem>
                                            <SelectItem value="conference">Conference</SelectItem>
                                            <SelectItem value="symposium">Symposium</SelectItem>
                                            <SelectItem value="training">Training</SelectItem>
                                            <SelectItem value="other">Other</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <Label>Event Type *</Label>
                                    <Select value={data.event_type} onValueChange={(value) => setData('event_type', value)}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="in-person">In-Person</SelectItem>
                                            <SelectItem value="virtual">Virtual</SelectItem>
                                            <SelectItem value="hybrid">Hybrid</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Date & Time</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="start_date">Start Date & Time *</Label>
                                    <Input
                                        id="start_date"
                                        type="datetime-local"
                                        value={data.start_date}
                                        onChange={(e) => setData('start_date', e.target.value)}
                                        required
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="end_date">End Date & Time *</Label>
                                    <Input
                                        id="end_date"
                                        type="datetime-local"
                                        value={data.end_date}
                                        onChange={(e) => setData('end_date', e.target.value)}
                                        required
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="registration_deadline">Registration Deadline</Label>
                                <Input
                                    id="registration_deadline"
                                    type="datetime-local"
                                    value={data.registration_deadline}
                                    onChange={(e) => setData('registration_deadline', e.target.value)}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Location & Settings</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="location">Physical Location</Label>
                                <Input
                                    id="location"
                                    value={data.location}
                                    onChange={(e) => setData('location', e.target.value)}
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="virtual_link">Virtual Link</Label>
                                <Input
                                    id="virtual_link"
                                    type="url"
                                    value={data.virtual_link}
                                    onChange={(e) => setData('virtual_link', e.target.value)}
                                />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="capacity">Capacity</Label>
                                    <Input
                                        id="capacity"
                                        type="number"
                                        min="1"
                                        value={data.capacity}
                                        onChange={(e) => setData('capacity', e.target.value)}
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="cme_credits">CME Credits</Label>
                                    <Input
                                        id="cme_credits"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={data.cme_credits}
                                        onChange={(e) => setData('cme_credits', e.target.value)}
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="requirements">Requirements</Label>
                                <Textarea
                                    id="requirements"
                                    value={data.requirements}
                                    onChange={(e) => setData('requirements', e.target.value)}
                                    rows={3}
                                />
                            </div>

                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="requires_approval"
                                    checked={data.requires_approval}
                                    onCheckedChange={(checked) => setData('requires_approval', checked === true)}
                                />
                                <Label htmlFor="requires_approval" className="cursor-pointer font-normal">
                                    Require manual approval
                                </Label>
                            </div>

                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="is_published"
                                    checked={data.is_published}
                                    onCheckedChange={(checked) => setData('is_published', checked === true)}
                                />
                                <Label htmlFor="is_published" className="cursor-pointer font-normal">
                                    Published
                                </Label>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex justify-end gap-4">
                        <Button type="button" variant="outline" asChild>
                            <Link href="/events/manage">Cancel</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Saving...' : 'Save Changes'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

