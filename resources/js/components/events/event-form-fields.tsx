import { RichTextEditor } from '@/components/rich-text-editor';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface EventFormData {
    title: string;
    description: string;
    event_category: string;
    event_type: string;
    start_date: string;
    end_date: string;
    registration_deadline: string;
    location: string;
    virtual_link: string;
    capacity: string;
    price: string;
    is_free: boolean;
    cme_credits: string;
    credit_type?: string | null;
    requirements: string;
    requires_approval: boolean;
    is_published: boolean;
    image?: File | null;
    existing_image?: string | null;
}

interface Props {
    data: EventFormData;
    setData: (data: EventFormData) => void;
    errors?: Record<string, string>;
}

export function EventFormFields({ data, setData, errors = {} }: Props) {
    const handleImageChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0] || null;
        setData({ ...data, image: file });
    };

    return (
        <div>
            {/* Basic Information */}
            <div className="space-y-4">
                <h3 className="text-sm font-semibold">General Information</h3>

                {/* Event Poster/Image */}
                <div className="space-y-2">
                    <Label htmlFor="image">Event Poster/Banner Image</Label>
                    <Input
                        id="image"
                        type="file"
                        accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                        onChange={handleImageChange}
                    />
                    {errors.image && (
                        <p className="text-sm text-destructive">
                            {errors.image}
                        </p>
                    )}
                    {data.existing_image && (
                        <div className="mt-2">
                            <img
                                src={data.existing_image}
                                alt="Current poster"
                                className="h-32 w-auto rounded-lg object-cover"
                            />
                            <p className="mt-1 text-xs text-muted-foreground">
                                Current poster (upload new to replace)
                            </p>
                        </div>
                    )}
                    <p className="text-xs text-muted-foreground">
                        Recommended: 1200x630px, Max 5MB (JPG, PNG, GIF, WebP)
                    </p>
                </div>

                <div className="space-y-2">
                    <Label htmlFor="title">Event Title *</Label>
                    <Input
                        id="title"
                        value={data.title}
                        onChange={(e) =>
                            setData({ ...data, title: e.target.value })
                        }
                        placeholder="e.g., Annual Medical Convention 2025"
                        required
                    />
                    {errors.title && (
                        <p className="text-sm text-destructive">
                            {errors.title}
                        </p>
                    )}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="description">Description</Label>
                    <RichTextEditor
                        value={data.description}
                        onChange={(value) =>
                            setData({ ...data, description: value })
                        }
                        placeholder="Describe your event with formatted text, lists, and links..."
                    />
                    {errors.description && (
                        <p className="text-sm text-destructive">
                            {errors.description}
                        </p>
                    )}
                    <p className="text-xs text-muted-foreground">
                        Use the toolbar to format text with bold, italic, lists,
                        links, and more.
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-2">
                        <Label>Category *</Label>
                        <Select
                            value={data.event_category}
                            onValueChange={(value) =>
                                setData({ ...data, event_category: value })
                            }
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="convention">
                                    Convention
                                </SelectItem>
                                <SelectItem value="workshop">
                                    Workshop
                                </SelectItem>
                                <SelectItem value="seminar">Seminar</SelectItem>
                                <SelectItem value="cme">CME</SelectItem>
                                <SelectItem value="conference">
                                    Conference
                                </SelectItem>
                                <SelectItem value="symposium">
                                    Symposium
                                </SelectItem>
                                <SelectItem value="training">
                                    Training
                                </SelectItem>
                                <SelectItem value="other">Other</SelectItem>
                            </SelectContent>
                        </Select>
                        {errors.event_category && (
                            <p className="text-sm text-destructive">
                                {errors.event_category}
                            </p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label>Event Type *</Label>
                        <Select
                            value={data.event_type}
                            onValueChange={(value) =>
                                setData({ ...data, event_type: value })
                            }
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="in-person">
                                    In-Person
                                </SelectItem>
                                <SelectItem value="virtual">Virtual</SelectItem>
                                <SelectItem value="hybrid">Hybrid</SelectItem>
                            </SelectContent>
                        </Select>
                        {errors.event_type && (
                            <p className="text-sm text-destructive">
                                {errors.event_type}
                            </p>
                        )}
                    </div>
                </div>
            </div>

            {/* Date & Time */}
            <div className="space-y-4">
                <h3 className="text-sm font-semibold">Date & Time</h3>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="start_date">Start Date & Time *</Label>
                        <Input
                            id="start_date"
                            type="datetime-local"
                            value={data.start_date}
                            onChange={(e) =>
                                setData({ ...data, start_date: e.target.value })
                            }
                            required
                        />
                        {errors.start_date && (
                            <p className="text-sm text-destructive">
                                {errors.start_date}
                            </p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="end_date">End Date & Time *</Label>
                        <Input
                            id="end_date"
                            type="datetime-local"
                            value={data.end_date}
                            onChange={(e) =>
                                setData({ ...data, end_date: e.target.value })
                            }
                            required
                        />
                        {errors.end_date && (
                            <p className="text-sm text-destructive">
                                {errors.end_date}
                            </p>
                        )}
                    </div>
                </div>

                <div className="space-y-2">
                    <Label htmlFor="registration_deadline">
                        Registration Deadline
                    </Label>
                    <Input
                        id="registration_deadline"
                        type="datetime-local"
                        value={data.registration_deadline}
                        onChange={(e) =>
                            setData({
                                ...data,
                                registration_deadline: e.target.value,
                            })
                        }
                    />
                    {errors.registration_deadline && (
                        <p className="text-sm text-destructive">
                            {errors.registration_deadline}
                        </p>
                    )}
                </div>
            </div>

            {/* Location & Venue */}
            <div className="space-y-4">
                <h3 className="text-sm font-semibold">Location & Venue</h3>

                <div className="space-y-2">
                    <Label htmlFor="location">Physical Location</Label>
                    <Input
                        id="location"
                        value={data.location}
                        onChange={(e) =>
                            setData({ ...data, location: e.target.value })
                        }
                        placeholder="e.g., Manila Hotel, Philippines"
                    />
                    {errors.location && (
                        <p className="text-sm text-destructive">
                            {errors.location}
                        </p>
                    )}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="virtual_link">
                        Virtual Link (for online/hybrid events)
                    </Label>
                    <Input
                        id="virtual_link"
                        type="url"
                        value={data.virtual_link}
                        onChange={(e) =>
                            setData({ ...data, virtual_link: e.target.value })
                        }
                        placeholder="https://zoom.us/..."
                    />
                    {errors.virtual_link && (
                        <p className="text-sm text-destructive">
                            {errors.virtual_link}
                        </p>
                    )}
                </div>
            </div>

            {/* Registration Settings */}
            <div className="space-y-4">
                <h3 className="text-sm font-semibold">Registration Settings</h3>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="capacity">
                            Capacity (max attendees)
                        </Label>
                        <Input
                            id="capacity"
                            type="number"
                            min="1"
                            value={data.capacity}
                            onChange={(e) =>
                                setData({ ...data, capacity: e.target.value })
                            }
                            placeholder="Leave empty for unlimited"
                        />
                        {errors.capacity && (
                            <p className="text-sm text-destructive">
                                {errors.capacity}
                            </p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="cme_credits">CME/CPD Credits</Label>
                        <Input
                            id="cme_credits"
                            type="number"
                            step="0.01"
                            min="0"
                            value={data.cme_credits}
                            onChange={(e) =>
                                setData({
                                    ...data,
                                    cme_credits: e.target.value,
                                })
                            }
                            placeholder="0.00"
                        />
                        {errors.cme_credits && (
                            <p className="text-sm text-destructive">
                                {errors.cme_credits}
                            </p>
                        )}
                    </div>

                    {data.cme_credits && parseFloat(data.cme_credits) > 0 && (
                        <div className="space-y-2">
                            <Label htmlFor="credit_type">
                                Credit Type{' '}
                                <span className="text-destructive">*</span>
                            </Label>
                            <Select
                                value={data.credit_type || 'cme'}
                                onValueChange={(value) =>
                                    setData({
                                        ...data,
                                        credit_type: value,
                                    })
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select credit type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="cme">CME (Continuing Medical Education)</SelectItem>
                                    <SelectItem value="cpd">CPD (Continuing Professional Development)</SelectItem>
                                </SelectContent>
                            </Select>
                            <p className="text-xs text-muted-foreground">
                                CME: Clinical knowledge & skills | CPD: Professional development (leadership, ethics, etc.)
                            </p>
                            {errors.credit_type && (
                                <p className="text-sm text-destructive">
                                    {errors.credit_type}
                                </p>
                            )}
                        </div>
                    )}
                </div>

                <div className="space-y-3">
                    <div className="flex items-center space-x-2">
                        <Checkbox
                            id="is_free"
                            checked={data.is_free}
                            onCheckedChange={(checked) => {
                                setData({
                                    ...data,
                                    is_free: checked === true,
                                    price: checked === true ? '0' : data.price,
                                });
                            }}
                        />
                        <Label
                            htmlFor="is_free"
                            className="cursor-pointer font-normal"
                        >
                            Free event (no registration fee)
                        </Label>
                    </div>

                    {!data.is_free && (
                        <div className="space-y-2">
                            <Label htmlFor="price">Registration Fee (₱)</Label>
                            <Input
                                id="price"
                                type="number"
                                step="0.01"
                                min="0"
                                value={data.price}
                                onChange={(e) =>
                                    setData({ ...data, price: e.target.value })
                                }
                                placeholder="0.00"
                            />
                            {errors.price && (
                                <p className="text-sm text-destructive">
                                    {errors.price}
                                </p>
                            )}
                            <p className="text-xs text-muted-foreground">
                                Payment will be processed via Stripe (Phase 2)
                            </p>
                        </div>
                    )}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="requirements">
                        Requirements/Prerequisites
                    </Label>
                    <RichTextEditor
                        value={data.requirements}
                        onChange={(value) =>
                            setData({ ...data, requirements: value })
                        }
                        placeholder="List any requirements or prerequisites for attendees..."
                    />
                    {errors.requirements && (
                        <p className="text-sm text-destructive">
                            {errors.requirements}
                        </p>
                    )}
                </div>

                <div className="space-y-3">
                    <div className="flex items-center space-x-2">
                        <Checkbox
                            id="requires_approval"
                            checked={data.requires_approval}
                            onCheckedChange={(checked) =>
                                setData({
                                    ...data,
                                    requires_approval: checked === true,
                                })
                            }
                        />
                        <Label
                            htmlFor="requires_approval"
                            className="cursor-pointer font-normal"
                        >
                            Require manual approval for registrations
                        </Label>
                    </div>

                    <div className="flex items-center space-x-2">
                        <Checkbox
                            id="is_published"
                            checked={data.is_published}
                            onCheckedChange={(checked) =>
                                setData({
                                    ...data,
                                    is_published: checked === true,
                                })
                            }
                        />
                        <Label
                            htmlFor="is_published"
                            className="cursor-pointer font-normal"
                        >
                            Publish event immediately
                        </Label>
                    </div>
                </div>
            </div>
        </div>
    );
}
