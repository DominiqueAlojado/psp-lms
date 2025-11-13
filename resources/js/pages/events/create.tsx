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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Events', href: '/events' },
    { title: 'Manage', href: '/events/manage' },
    { title: 'Create Event', href: '/events/create' },
];

export default function CreateEvent() {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        event_category: 'other',
        event_type: 'in-person',
        start_date: '',
        end_date: '',
        registration_deadline: '',
        location: '',
        virtual_link: '',
        capacity: '',
        cme_credits: '',
        requirements: '',
        requires_approval: false,
        is_published: false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/events');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Event" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex items-start justify-between gap-4">
                    <HeadingSmall
                        title="Create Event"
                        description="Create a new event or convention"
                    />
                    <Button asChild variant="outline">
                        <Link href="/events/manage">Cancel</Link>
                    </Button>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Basic Information</CardTitle>
                            <CardDescription>Enter the basic details of your event</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="title">Event Title *</Label>
                                <Input
                                    id="title"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    placeholder="e.g., Annual Medical Convention 2025"
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
                                    placeholder="Describe your event..."
                                    rows={4}
                                />
                                {errors.description && <p className="text-sm text-destructive">{errors.description}</p>}
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="event_category">Category *</Label>
                                    <Select value={data.event_category} onValueChange={(value) => setData('event_category', value)}>
                                        <SelectTrigger id="event_category">
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
                                    {errors.event_category && <p className="text-sm text-destructive">{errors.event_category}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="event_type">Event Type *</Label>
                                    <Select value={data.event_type} onValueChange={(value) => setData('event_type', value)}>
                                        <SelectTrigger id="event_type">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="in-person">In-Person</SelectItem>
                                            <SelectItem value="virtual">Virtual</SelectItem>
                                            <SelectItem value="hybrid">Hybrid</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.event_type && <p className="text-sm text-destructive">{errors.event_type}</p>}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Date & Time</CardTitle>
                            <CardDescription>Set when your event will take place</CardDescription>
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
                                    {errors.start_date && <p className="text-sm text-destructive">{errors.start_date}</p>}
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
                                    {errors.end_date && <p className="text-sm text-destructive">{errors.end_date}</p>}
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
                                {errors.registration_deadline && <p className="text-sm text-destructive">{errors.registration_deadline}</p>}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Location & Venue</CardTitle>
                            <CardDescription>Where will the event take place?</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="location">Physical Location</Label>
                                <Input
                                    id="location"
                                    value={data.location}
                                    onChange={(e) => setData('location', e.target.value)}
                                    placeholder="e.g., Manila Hotel, Philippines"
                                />
                                {errors.location && <p className="text-sm text-destructive">{errors.location}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="virtual_link">Virtual Link (for online/hybrid events)</Label>
                                <Input
                                    id="virtual_link"
                                    type="url"
                                    value={data.virtual_link}
                                    onChange={(e) => setData('virtual_link', e.target.value)}
                                    placeholder="https://zoom.us/..."
                                />
                                {errors.virtual_link && <p className="text-sm text-destructive">{errors.virtual_link}</p>}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Registration Settings</CardTitle>
                            <CardDescription>Configure how attendees can register</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="capacity">Capacity (max attendees)</Label>
                                    <Input
                                        id="capacity"
                                        type="number"
                                        min="1"
                                        value={data.capacity}
                                        onChange={(e) => setData('capacity', e.target.value)}
                                        placeholder="Leave empty for unlimited"
                                    />
                                    {errors.capacity && <p className="text-sm text-destructive">{errors.capacity}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="cme_credits">CME/CPD Credits</Label>
                                    <Input
                                        id="cme_credits"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={data.cme_credits}
                                        onChange={(e) => setData('cme_credits', e.target.value)}
                                        placeholder="0.00"
                                    />
                                    {errors.cme_credits && <p className="text-sm text-destructive">{errors.cme_credits}</p>}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="requirements">Requirements/Prerequisites</Label>
                                <Textarea
                                    id="requirements"
                                    value={data.requirements}
                                    onChange={(e) => setData('requirements', e.target.value)}
                                    placeholder="Any requirements for attendees..."
                                    rows={3}
                                />
                                {errors.requirements && <p className="text-sm text-destructive">{errors.requirements}</p>}
                            </div>

                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="requires_approval"
                                    checked={data.requires_approval}
                                    onCheckedChange={(checked) => setData('requires_approval', checked === true)}
                                />
                                <Label htmlFor="requires_approval" className="cursor-pointer font-normal">
                                    Require manual approval for registrations
                                </Label>
                            </div>

                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="is_published"
                                    checked={data.is_published}
                                    onCheckedChange={(checked) => setData('is_published', checked === true)}
                                />
                                <Label htmlFor="is_published" className="cursor-pointer font-normal">
                                    Publish event immediately
                                </Label>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex justify-end gap-4">
                        <Button type="button" variant="outline" asChild>
                            <Link href="/events/manage">Cancel</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Creating...' : 'Create Event'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

