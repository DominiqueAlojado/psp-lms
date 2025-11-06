import { Button } from '@/components/ui/button';
import {
    Command,
    CommandGroup,
    CommandInput,
    CommandList,
} from '@/components/ui/command';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { router } from '@inertiajs/react';
import { Check, ChevronsUpDown, Plus } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

interface Topic {
    id: number;
    name: string;
    slug: string;
    is_global: boolean;
}

interface TopicSelectorProps {
    value?: number | null;
    onChange: (topicId: number | null) => void;
    label?: string;
    placeholder?: string;
    className?: string;
}

export function TopicSelector({
    value,
    onChange,
    label = 'Topic (Optional)',
    placeholder = 'Select topic...',
    className,
}: TopicSelectorProps) {
    const [topics, setTopics] = useState<Topic[]>([]);
    const [open, setOpen] = useState(false);
    const [topicSearch, setTopicSearch] = useState('');
    const [isAddingTopic, setIsAddingTopic] = useState(false);
    const [newTopicName, setNewTopicName] = useState('');

    // Fetch topics on mount
    useEffect(() => {
        fetch('/topics')
            .then((res) => res.json())
            .then((data) => setTopics(data))
            .catch((err) => console.error('Failed to fetch topics:', err));
    }, []);

    const createNewTopic = () => {
        if (!newTopicName.trim()) {
            toast.error('Please enter a topic name');
            return;
        }

        router.post(
            '/topics',
            { name: newTopicName },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    // Refetch topics to get the newly created one
                    fetch('/topics')
                        .then((res) => res.json())
                        .then((data) => {
                            setTopics(data);
                            // Find and auto-select the new topic
                            const newTopic = data.find(
                                (t: Topic) => t.name === newTopicName,
                            );
                            if (newTopic) {
                                onChange(newTopic.id);
                                toast.success(`Topic "${newTopicName}" created and selected!`);
                            }
                            setNewTopicName('');
                            setIsAddingTopic(false);
                        })
                        .catch((err) => {
                            console.error('Failed to refetch topics:', err);
                            toast.error('Failed to load topics');
                            setNewTopicName('');
                            setIsAddingTopic(false);
                        });
                },
                onError: (errors) => {
                    console.error('Error creating topic:', errors);
                    toast.error('Failed to create topic');
                },
            },
        );
    };

    const selectedTopic = topics.find((topic) => topic.id === value);

    return (
        <div className={cn('space-y-2', className)}>
            <Label>{label}</Label>
            <div className="flex gap-2">
                <Popover open={open} onOpenChange={setOpen}>
                    <PopoverTrigger asChild>
                        <Button
                            type="button"
                            variant="outline"
                            role="combobox"
                            aria-expanded={open}
                            className="flex-1 justify-between"
                        >
                            {selectedTopic ? selectedTopic.name : placeholder}
                            <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent className="w-[400px] p-0" align="start">
                        <Command>
                            <CommandInput
                                placeholder="Search topics..."
                                value={topicSearch}
                                onValueChange={setTopicSearch}
                            />
                            <CommandList>
                                <CommandGroup>
                                    {!topicSearch && (
                                        <div
                                            className="relative flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground"
                                            onClick={() => {
                                                onChange(null);
                                                setOpen(false);
                                                setTopicSearch('');
                                            }}
                                        >
                                            <Check
                                                className={cn(
                                                    'mr-2 h-4 w-4',
                                                    !value
                                                        ? 'opacity-100'
                                                        : 'opacity-0',
                                                )}
                                            />
                                            No topic
                                        </div>
                                    )}
                                    {topics
                                        .filter((topic) =>
                                            topic.name
                                                .toLowerCase()
                                                .includes(
                                                    topicSearch.toLowerCase(),
                                                ),
                                        )
                                        .map((topic) => (
                                            <div
                                                key={topic.id}
                                                className={cn(
                                                    'relative flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground',
                                                    value === topic.id &&
                                                        'bg-accent',
                                                )}
                                                onClick={() => {
                                                    onChange(topic.id);
                                                    setOpen(false);
                                                    setTopicSearch('');
                                                }}
                                            >
                                                <Check
                                                    className={cn(
                                                        'mr-2 h-4 w-4',
                                                        value === topic.id
                                                            ? 'opacity-100'
                                                            : 'opacity-0',
                                                    )}
                                                />
                                                {topic.name}{' '}
                                                {topic.is_global && (
                                                    <span className="text-muted-foreground">
                                                        (Global)
                                                    </span>
                                                )}
                                            </div>
                                        ))}
                                    {topicSearch &&
                                        topics.filter((topic) =>
                                            topic.name
                                                .toLowerCase()
                                                .includes(
                                                    topicSearch.toLowerCase(),
                                                ),
                                        ).length === 0 && (
                                            <div className="py-6 text-center text-sm text-muted-foreground">
                                                No topic found.
                                            </div>
                                        )}
                                </CommandGroup>
                            </CommandList>
                        </Command>
                    </PopoverContent>
                </Popover>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => setIsAddingTopic(true)}
                >
                    <Plus className="h-4 w-4" />
                </Button>
            </div>
            {isAddingTopic && (
                <div className="flex gap-2 rounded border bg-muted/30 p-3">
                    <Input
                        placeholder="New topic name"
                        value={newTopicName}
                        onChange={(e) => setNewTopicName(e.target.value)}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                createNewTopic();
                            }
                        }}
                    />
                    <Button type="button" size="sm" onClick={createNewTopic}>
                        Add
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => {
                            setIsAddingTopic(false);
                            setNewTopicName('');
                        }}
                    >
                        Cancel
                    </Button>
                </div>
            )}
        </div>
    );
}

