import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import { QuestionFormFields } from './question-form-fields';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function CreateQuestionSheet({ open, onOpenChange }: Props) {
    const [formData, setFormData] = useState({
        topic_id: null as number | null,
        question_type: 'multiple_choice',
        question_text: '',
        points: 1,
        explanation: '',
        difficulty_level: 'medium',
        choices: [
            { choice_text: '', is_correct: true },
            { choice_text: '', is_correct: false },
            { choice_text: '', is_correct: false },
            { choice_text: '', is_correct: false },
        ],
        image: null as File | null,
    });

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        setErrors({});

        const data = new FormData();
        
        if (formData.topic_id) {
            data.append('topic_id', formData.topic_id.toString());
        }
        data.append('question_type', formData.question_type);
        data.append('question_text', formData.question_text);
        data.append('points', formData.points.toString());
        data.append('explanation', formData.explanation);
        data.append('difficulty_level', formData.difficulty_level);

        // Add choices
        formData.choices.forEach((choice, index) => {
            data.append(`choices[${index}][choice_text]`, choice.choice_text);
            data.append(`choices[${index}][is_correct]`, choice.is_correct ? '1' : '0');
        });

        if (formData.image) {
            data.append('image', formData.image);
        }

        router.post('/question-bank', data, {
            forceFormData: true,
            onSuccess: () => {
                onOpenChange(false);
                setFormData({
                    topic_id: null,
                    question_type: 'multiple_choice',
                    question_text: '',
                    points: 1,
                    explanation: '',
                    difficulty_level: 'medium',
                    choices: [
                        { choice_text: '', is_correct: true },
                        { choice_text: '', is_correct: false },
                        { choice_text: '', is_correct: false },
                        { choice_text: '', is_correct: false },
                    ],
                    image: null,
                });
            },
            onError: (err) => {
                setErrors(err as Record<string, string>);
            },
            onFinish: () => {
                setIsSubmitting(false);
            },
        });
    };

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent className="overflow-y-auto sm:max-w-2xl">
                <SheetHeader>
                    <SheetTitle>Create Question</SheetTitle>
                    <SheetDescription>
                        Add a new question to your question bank. This question can be reused in multiple exams.
                    </SheetDescription>
                </SheetHeader>

                <form onSubmit={handleSubmit} className="mt-6 space-y-6">
                    <QuestionFormFields data={formData} setData={setFormData} errors={errors} />

                    <div className="flex justify-end gap-3 border-t pt-6">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                            disabled={isSubmitting}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={isSubmitting}>
                            {isSubmitting ? 'Creating...' : 'Create Question'}
                        </Button>
                    </div>
                </form>
            </SheetContent>
        </Sheet>
    );
}

