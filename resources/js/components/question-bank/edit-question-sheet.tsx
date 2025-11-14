import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { QuestionFormFields } from './question-form-fields';

interface Question {
    id: number;
    topic_id: number | null;
    question_type: string;
    question_text: string;
    points: number;
    explanation: string;
    difficulty_level: string;
    image_path: string | null;
    choices: Array<{
        choice_text: string;
        is_correct: boolean;
    }>;
}

interface Props {
    question: Question | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function EditQuestionSheet({ question, open, onOpenChange }: Props) {
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
        ],
        image: null as File | null,
        existing_image: null as string | null,
    });

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        if (question) {
            setFormData({
                topic_id: question.topic_id,
                question_type: question.question_type,
                question_text: question.question_text,
                points: question.points,
                explanation: question.explanation || '',
                difficulty_level: question.difficulty_level || 'medium',
                choices: question.choices,
                image: null,
                existing_image: question.image_path
                    ? `/storage/${question.image_path}`
                    : null,
            });
        }
    }, [question]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!question) return;

        setIsSubmitting(true);
        setErrors({});

        const data = new FormData();
        data.append('_method', 'PATCH');

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
            data.append(
                `choices[${index}][is_correct]`,
                choice.is_correct ? '1' : '0',
            );
        });

        if (formData.image) {
            data.append('image', formData.image);
        }

        router.post(`/question-bank/${question.id}`, data, {
            forceFormData: true,
            onSuccess: () => {
                onOpenChange(false);
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
                    <SheetTitle>Edit Question</SheetTitle>
                    <SheetDescription>
                        Update this question. Changes will not affect existing
                        exams using this question.
                    </SheetDescription>
                </SheetHeader>

                <form onSubmit={handleSubmit} className="p-4">
                    <QuestionFormFields
                        data={formData}
                        setData={setFormData}
                        errors={errors}
                    />

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
                            {isSubmitting ? 'Updating...' : 'Update Question'}
                        </Button>
                    </div>
                </form>
            </SheetContent>
        </Sheet>
    );
}
