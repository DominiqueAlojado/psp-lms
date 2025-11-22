import { TopicSelector } from '@/components/topic-selector';
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

interface Choice {
    choice_text: string;
    is_correct: boolean;
}

interface QuestionFormData {
    topic_id: number | null;
    question_type: string;
    question_text: string;
    points: number;
    explanation: string;
    difficulty_level: string;
    choices: Choice[];
    image?: File | null;
    existing_image?: string | null;
}

interface Props {
    data: QuestionFormData;
    setData: (data: QuestionFormData) => void;
    errors?: Record<string, string>;
}

export function QuestionFormFields({ data, setData, errors = {} }: Props) {
    const handleImageChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0] || null;
        setData({ ...data, image: file });
    };

    const updateChoice = (index: number, field: keyof Choice, value: string | boolean) => {
        const newChoices = [...data.choices];
        newChoices[index] = { ...newChoices[index], [field]: value };
        
        // For multiple_choice, ensure only one answer is correct
        if (field === 'is_correct' && data.question_type === 'multiple_choice' && value === true) {
            // Uncheck all other choices
            newChoices.forEach((choice, idx) => {
                if (idx !== index) {
                    choice.is_correct = false;
                }
            });
        }
        
        setData({ ...data, choices: newChoices });
    };

    return (
        <div className="space-y-6">
            {/* Basic Info */}
            <div className="space-y-4">
                <h3 className="text-sm font-semibold">Basic Information</h3>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-2">
                        <Label>Type *</Label>
                        <Select value={data.question_type} onValueChange={(value) => {
                            const newData = { ...data, question_type: value };
                            // Adjust choices based on type
                            if (value === 'true_false') {
                                newData.choices = [
                                    { choice_text: 'True', is_correct: true },
                                    { choice_text: 'False', is_correct: false },
                                ];
                            } else if (data.choices.length < 2) {
                                newData.choices = [
                                    { choice_text: '', is_correct: true },
                                    { choice_text: '', is_correct: false },
                                ];
                            }
                            setData(newData);
                        }}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="multiple_choice">Multiple Choice</SelectItem>
                                <SelectItem value="multiple_select">Multiple Select</SelectItem>
                                <SelectItem value="true_false">True/False</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Label>Difficulty *</Label>
                        <Select value={data.difficulty_level} onValueChange={(value) => setData({ ...data, difficulty_level: value })}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="easy">Easy</SelectItem>
                                <SelectItem value="medium">Medium</SelectItem>
                                <SelectItem value="hard">Hard</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-2">
                        <TopicSelector
                            value={data.topic_id}
                            onChange={(topicId) => setData({ ...data, topic_id: topicId })}
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="points">Points *</Label>
                        <Input
                            id="points"
                            type="number"
                            min="1"
                            value={data.points}
                            onChange={(e) => setData({ ...data, points: parseInt(e.target.value) || 1 })}
                        />
                    </div>
                </div>

                <div className="space-y-2">
                    <Label htmlFor="image">Question Image (Optional)</Label>
                    <Input
                        id="image"
                        type="file"
                        accept="image/*"
                        onChange={handleImageChange}
                    />
                    {data.existing_image && (
                        <img src={data.existing_image} alt="Current" className="mt-2 h-24 w-auto rounded" />
                    )}
                </div>
            </div>

            {/* Question */}
            <div className="space-y-4">
                <h3 className="text-sm font-semibold">Question</h3>
                <div className="space-y-2">
                    <Label>Question Text *</Label>
                    <RichTextEditor
                        value={data.question_text}
                        onChange={(value) => setData({ ...data, question_text: value })}
                        placeholder="Enter your question..."
                    />
                </div>
            </div>

            {/* Choices */}
            <div className="space-y-4">
                <h3 className="text-sm font-semibold">Answer Choices</h3>
                {data.question_type === 'true_false' ? (
                    <div className="space-y-2">
                        <Label>Correct Answer</Label>
                        <Select value={data.choices[0]?.is_correct ? 'true' : 'false'} onValueChange={(value) => {
                            const newChoices = [
                                { choice_text: 'True', is_correct: value === 'true' },
                                { choice_text: 'False', is_correct: value === 'false' },
                            ];
                            setData({ ...data, choices: newChoices });
                        }}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="true">True</SelectItem>
                                <SelectItem value="false">False</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                ) : (
                    <div className="space-y-3">
                        {data.choices.map((choice, index) => (
                            <div key={index} className="flex items-start gap-3 rounded-lg border p-3">
                                <Checkbox
                                    checked={choice.is_correct}
                                    onCheckedChange={(checked) => updateChoice(index, 'is_correct', checked === true)}
                                    className="mt-1"
                                />
                                <div className="flex-1 space-y-1">
                                    <Label className="text-xs text-muted-foreground">
                                        Choice {index + 1} {choice.is_correct && '(Correct)'}
                                    </Label>
                                    <Input
                                        value={choice.choice_text}
                                        onChange={(e) => updateChoice(index, 'choice_text', e.target.value)}
                                        placeholder={`Enter choice ${index + 1}...`}
                                    />
                                </div>
                            </div>
                        ))}
                        <p className="text-xs text-muted-foreground">
                            {data.question_type === 'multiple_select' 
                                ? 'Check all correct answers'
                                : 'Check the correct answer'}
                        </p>
                    </div>
                )}
            </div>

            {/* Explanation */}
            <div className="space-y-4">
                <h3 className="text-sm font-semibold">Explanation (Optional)</h3>
                <RichTextEditor
                    value={data.explanation}
                    onChange={(value) => setData({ ...data, explanation: value })}
                    placeholder="Explain why the answer is correct..."
                />
            </div>
        </div>
    );
}

