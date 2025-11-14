import { Button } from '@/components/ui/button';
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
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';

interface PageProps {
    assessmentId?: number;
}

// Define exam categories
const EXAM_CATEGORIES = [
    'Long Quiz',
    'Short Quiz',
    'Practical Exam',
    'Laboratory Exam',
    'Midterm Exam',
    'Final Exam',
    'Preliminary Exam',
    'Diagnostic Exam',
    'Pre-test',
    'Post-test',
    'Mock Exam',
    'Other',
] as const;

export default function CreateAssessment({
    assessmentId: propAssessmentId,
}: PageProps) {
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [examCategory, setExamCategory] = useState('');
    const [passingScore, setPassingScore] = useState<number | ''>('');
    const [duration, setDuration] = useState<number | ''>('');
    const [randomizeQuestions, setRandomizeQuestions] = useState(false);
    const [randomizeChoices, setRandomizeChoices] = useState(false);
    const [showResultsImmediately, setShowResultsImmediately] = useState(true);
    const [allowReview, setAllowReview] = useState(true);
    const [isPublished, setIsPublished] = useState(false);
    const [availableFrom, setAvailableFrom] = useState('');
    const [availableUntil, setAvailableUntil] = useState('');

    // Get assessment ID from props (passed from backend)
    const assessmentId = propAssessmentId || null;

    const createExam = () => {
        if (!title) {
            toast.error('Please enter a title');
            return;
        }

        // Step 1: Create exam metadata
        router.post(
            '/assessments',
            {
                title,
                description: description || null,
                exam_category: examCategory || null,
                passing_score: passingScore === '' ? 0 : passingScore,
                duration_minutes: duration === '' ? null : duration,
                randomize_questions: randomizeQuestions,
                randomize_choices: randomizeChoices,
                show_results_immediately: showResultsImmediately,
                allow_review: allowReview,
                is_published: isPublished,
                available_from: availableFrom || null,
                available_until: availableUntil || null,
            },
            {
                preserveScroll: true,
                onError: (errors) => {
                    console.error('Error creating exam:', errors);
                    toast.error('Failed to create exam');
                },
            },
        );
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Create Exam', href: '/institution-exams/create' },
            ]}
        >
            <Head title="Create Exam" />

            <div className="space-y-8 p-6">
                {/* Step 1: Exam Metadata */}
                <div className="space-y-4 rounded-lg border p-6">
                    <h3 className="text-lg font-semibold">
                        Step 1: Exam Details
                    </h3>
                    <div className="space-y-2">
                        <Label>Title</Label>
                        <Input
                            value={title}
                            onChange={(e) => setTitle(e.target.value)}
                            placeholder="Exam title"
                            disabled={!!assessmentId}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Description</Label>
                        <Textarea
                            value={description}
                            onChange={(e) => setDescription(e.target.value)}
                            placeholder="Describe the exam (optional)"
                            disabled={!!assessmentId}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Exam Category</Label>
                        <Select
                            value={examCategory}
                            onValueChange={setExamCategory}
                            disabled={!!assessmentId}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select category (optional)" />
                            </SelectTrigger>
                            <SelectContent>
                                {EXAM_CATEGORIES.map((category) => (
                                    <SelectItem key={category} value={category}>
                                        {category}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div className="space-y-2">
                            <Label>Passing Score</Label>
                            <Input
                                type="number"
                                value={passingScore}
                                onChange={(e) => {
                                    const value = e.target.value;
                                    setPassingScore(
                                        value === '' ? '' : parseInt(value, 10),
                                    );
                                }}
                                min={0}
                                disabled={!!assessmentId}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Duration (minutes)</Label>
                            <Input
                                type="number"
                                value={duration}
                                onChange={(e) =>
                                    setDuration(
                                        e.target.value
                                            ? parseInt(e.target.value)
                                            : '',
                                    )
                                }
                                min={1}
                                disabled={!!assessmentId}
                            />
                        </div>
                    </div>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Available From</Label>
                            <Input
                                type="datetime-local"
                                value={availableFrom}
                                onChange={(e) =>
                                    setAvailableFrom(e.target.value)
                                }
                                disabled={!!assessmentId}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Available Until</Label>
                            <Input
                                type="datetime-local"
                                value={availableUntil}
                                onChange={(e) =>
                                    setAvailableUntil(e.target.value)
                                }
                                disabled={!!assessmentId}
                            />
                        </div>
                    </div>
                    <div className="space-y-3">
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="randomize-questions"
                                checked={randomizeQuestions}
                                onCheckedChange={(checked) =>
                                    setRandomizeQuestions(checked as boolean)
                                }
                                disabled={!!assessmentId}
                            />
                            <Label htmlFor="randomize-questions">
                                Randomize questions
                            </Label>
                        </div>
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="randomize-choices"
                                checked={randomizeChoices}
                                onCheckedChange={(checked) =>
                                    setRandomizeChoices(checked as boolean)
                                }
                                disabled={!!assessmentId}
                            />
                            <Label htmlFor="randomize-choices">
                                Randomize answer choices
                            </Label>
                        </div>
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="show-results"
                                checked={showResultsImmediately}
                                onCheckedChange={(checked) =>
                                    setShowResultsImmediately(
                                        checked as boolean,
                                    )
                                }
                                disabled={!!assessmentId}
                            />
                            <Label htmlFor="show-results">
                                Show results immediately after submission
                            </Label>
                        </div>
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="allow-review"
                                checked={allowReview}
                                onCheckedChange={(checked) =>
                                    setAllowReview(checked as boolean)
                                }
                                disabled={!!assessmentId}
                            />
                            <Label htmlFor="allow-review">
                                Allow residents to review answers
                            </Label>
                        </div>
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="is-published"
                                checked={isPublished}
                                onCheckedChange={(checked) =>
                                    setIsPublished(checked as boolean)
                                }
                                disabled={!!assessmentId}
                            />
                            <Label htmlFor="is-published">
                                Publish exam (make it visible to residents)
                            </Label>
                        </div>
                    </div>
                    {!assessmentId && (
                        <Button onClick={createExam}>Create Exam</Button>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
