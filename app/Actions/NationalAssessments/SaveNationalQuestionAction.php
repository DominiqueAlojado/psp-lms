<?php

namespace App\Actions\NationalAssessments;

use App\Models\National\NationalAssessment;
use App\Models\National\NationalQuestion;
use App\Models\Topic;
use App\Repositories\Contracts\NationalQuestionRepositoryInterface;
use Illuminate\Support\Facades\Storage;

class SaveNationalQuestionAction
{
    public function __construct(
        private readonly NationalQuestionRepositoryInterface $questionRepository,
    ) {}

    public function execute(NationalAssessment $assessment, array $validated): array
    {
        $hasValidId = ! empty($validated['id']) && $validated['id'] > 0;
        $isNewQuestion = ! $hasValidId;

        $question = null;
        $old = [
            'question_text' => null,
            'question_type' => null,
            'points' => null,
            'topic' => null,
            'choices' => [],
        ];

        if (! $isNewQuestion) {
            $question = $this->questionRepository->findForAssessment($assessment, (int) $validated['id']);
            if ($question) {
                $old = [
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'points' => $question->points,
                    'topic' => $question->topic,
                    'choices' => $question->choices->map(fn ($choice) => [
                        'choice_text' => $choice->choice_text,
                        'is_correct' => $choice->is_correct,
                    ])->toArray(),
                ];
            }
        }

        if (! $question) {
            $question = new NationalQuestion([
                'assessment_id' => $assessment->id,
                'order' => $this->questionRepository->getNextOrderForAssessment($assessment),
            ]);
        }

        $question->question_type = $validated['question_type'];
        $question->question_text = $validated['question_text'];
        $question->points = $validated['points'];
        $question->topic = $this->resolveTopicName($validated);

        $imagePath = $this->storeQuestionImage($validated['image'] ?? null);
        if ($imagePath) {
            $question->image_path = $imagePath;
        }

        if ($question->exists) {
            $this->questionRepository->update($question, $question->getAttributes());
        } else {
            $question = $this->questionRepository->createForAssessment($assessment, $question->getAttributes());
        }

        return [
            'question' => $question,
            'is_new' => $isNewQuestion,
            'image_path' => $imagePath,
            'topic_id' => $validated['topic_id'] ?? null,
            'old' => $old,
        ];
    }

    private function resolveTopicName(array $validated): ?string
    {
        if (! empty($validated['topic_id'])) {
            return Topic::find($validated['topic_id'])?->name;
        }

        return $validated['topic'] ?? null;
    }

    private function storeQuestionImage(?string $image): ?string
    {
        if (! $image || ! str_starts_with($image, 'data:image')) {
            return null;
        }

        $data = explode(',', $image, 2)[1] ?? null;
        if (! $data) {
            return null;
        }

        $binary = base64_decode($data);
        $path = 'national-questions/' . uniqid() . '_' . time() . '.png';
        Storage::disk('public')->put($path, $binary);

        return $path;
    }
}
