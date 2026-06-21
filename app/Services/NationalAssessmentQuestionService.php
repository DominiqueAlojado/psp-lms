<?php

namespace App\Services;

use App\Models\National\NationalAssessment;
use App\Models\National\NationalQuestion;
use App\Models\QuestionBank;
use App\Models\Topic;
use App\Models\User;
use App\Repositories\Contracts\NationalAssessmentRepositoryInterface;
use App\Repositories\Contracts\NationalQuestionChoiceRepositoryInterface;
use App\Repositories\Contracts\NationalQuestionRepositoryInterface;
use App\Repositories\Contracts\QuestionBankChoiceRepositoryInterface;
use App\Repositories\Contracts\QuestionBankRepositoryInterface;
use App\Repositories\Contracts\QuestionBankStatisticRepositoryInterface;
use App\Services\ActivityLog\NationalAssessmentActivityLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class NationalAssessmentQuestionService
{
    public function __construct(
        private readonly NationalAssessmentRepositoryInterface $assessmentRepository,
        private readonly NationalQuestionRepositoryInterface $questionRepository,
        private readonly NationalQuestionChoiceRepositoryInterface $questionChoiceRepository,
        private readonly QuestionBankRepositoryInterface $questionBankRepository,
        private readonly QuestionBankChoiceRepositoryInterface $questionBankChoiceRepository,
        private readonly QuestionBankStatisticRepositoryInterface $questionBankStatisticRepository,
        private readonly NationalAssessmentActivityLogService $activityLogService,
    ) {}

    public function storeQuestions(NationalAssessment $assessment, array $questions): void
    {
        $totalPointsAdded = 0;

        DB::transaction(function () use ($questions, $assessment, &$totalPointsAdded) {
            $order = $this->questionRepository->getNextOrderForAssessment($assessment);

            foreach ($questions as $questionData) {
                $question = $this->questionRepository->createForAssessment($assessment, [
                    'question_type' => $questionData['question_type'],
                    'question_text' => $questionData['question_text'],
                    'points' => $questionData['points'],
                    'difficulty_level' => $questionData['difficulty_level'] ?? null,
                    'topic' => $questionData['topic'] ?? null,
                    'order' => $order++,
                ]);

                $totalPointsAdded += (int) $questionData['points'];
                $this->replaceQuestionChoices($question, $questionData);
            }

            $this->assessmentRepository->update($assessment, [
                'total_points' => $assessment->total_points + $totalPointsAdded,
            ]);
        });

        $assessment->refresh();
        $this->activityLogService->logQuestionsAdded($assessment, count($questions), $totalPointsAdded);
    }

    public function addFromBank(NationalAssessment $assessment, array $questionIds): array
    {
        $bankQuestions = $this->questionBankRepository->findByIdsForOwnerType($questionIds, 'national');

        if ($bankQuestions->isEmpty()) {
            return ['added_count' => 0, 'skipped_count' => 0];
        }

        $totalPointsAdded = 0;
        $order = $this->questionRepository->getNextOrderForAssessment($assessment);
        $addedCount = 0;
        $skippedCount = 0;

        DB::transaction(function () use ($bankQuestions, $assessment, &$totalPointsAdded, &$order, &$addedCount, &$skippedCount) {
            $existingSignatures = $assessment->questions()
                ->get(['question_text', 'question_type'])
                ->map(fn ($question) => $this->buildQuestionSignature($question->question_text, $question->question_type));

            foreach ($bankQuestions as $bankQuestion) {
                $signature = $this->buildQuestionSignature($bankQuestion->question_text, $bankQuestion->question_type);

                if ($existingSignatures->contains($signature)) {
                    $skippedCount++;
                    continue;
                }

                $question = $this->questionRepository->createForAssessment($assessment, [
                    'question_type' => $bankQuestion->question_type,
                    'question_text' => $bankQuestion->question_text,
                    'points' => $bankQuestion->points,
                    'topic' => $bankQuestion->topic?->name,
                    'order' => $order++,
                    'image_path' => $bankQuestion->image_path,
                ]);

                $totalPointsAdded += (int) $bankQuestion->points;

                $this->questionChoiceRepository->createMany(
                    $question,
                    $bankQuestion->choices->map(fn ($choice) => [
                        'choice_text' => $choice->choice_text,
                        'is_correct' => $choice->is_correct,
                        'order' => $choice->order,
                    ])->all()
                );

                $this->questionBankRepository->incrementUsage($bankQuestion);
                $existingSignatures->push($signature);
                $addedCount++;

                $question->load('choices');
                $this->activityLogService->logQuestionAddedFromBank(
                    $assessment,
                    $question->id,
                    $question->question_text,
                    $question->question_type,
                    $question->points,
                    $question->topic,
                    $question->choices->map(fn ($choice) => [
                        'choice_text' => $choice->choice_text,
                        'is_correct' => $choice->is_correct,
                    ])->toArray(),
                    $bankQuestion->id
                );
            }

            $this->assessmentRepository->update($assessment, [
                'total_points' => $assessment->total_points + $totalPointsAdded,
            ]);
        });

        return [
            'added_count' => $addedCount,
            'skipped_count' => $skippedCount,
        ];
    }

    public function saveQuestion(NationalAssessment $assessment, array $validated, User $user): NationalQuestion
    {
        $hasValidId = ! empty($validated['id']) && $validated['id'] > 0;
        $isNewQuestion = ! $hasValidId;

        $question = null;
        $oldQuestionText = null;
        $oldQuestionType = null;
        $oldPoints = null;
        $oldTopic = null;
        $oldChoices = [];

        if (! $isNewQuestion) {
            $question = $this->questionRepository->findForAssessment($assessment, (int) $validated['id']);

            if ($question) {
                $oldQuestionText = $question->question_text;
                $oldQuestionType = $question->question_type;
                $oldPoints = $question->points;
                $oldTopic = $question->topic;
                $oldChoices = $question->choices->map(fn ($choice) => [
                    'choice_text' => $choice->choice_text,
                    'is_correct' => $choice->is_correct,
                ])->toArray();
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

        $choicesData = $this->replaceQuestionChoices($question, $validated);
        $topicId = $validated['topic_id'] ?? null;
        $topicName = $question->topic;
        $existsInBank = $this->questionBankRepository->existsForNationalCreator($question->question_text, $user->id);

        if ($isNewQuestion || ! $existsInBank) {
            try {
                $this->saveToQuestionBank($question, $choicesData, $imagePath, $topicId, $topicName, $user);
            } catch (\Throwable $exception) {
                Log::error('Failed to save question to question bank', [
                    'error' => $exception->getMessage(),
                    'question_id' => $question->id,
                ]);
            }
        }

        $this->assessmentRepository->update($assessment, [
            'total_points' => $this->assessmentRepository->sumQuestionPoints($assessment),
        ]);

        if ($isNewQuestion) {
            $this->activityLogService->logQuestionAdded(
                $assessment,
                $question->id,
                $question->question_text,
                $question->question_type,
                $question->points,
                $question->topic,
                $choicesData
            );
        } elseif ($oldQuestionText !== null) {
            $this->activityLogService->logQuestionUpdated(
                $assessment,
                $question->id,
                $question->question_text,
                $question->question_type,
                $question->points,
                $question->topic,
                $choicesData,
                $oldQuestionText,
                $oldQuestionType,
                $oldPoints,
                $oldTopic,
                $oldChoices
            );
        }

        return $question;
    }

    public function deleteQuestion(NationalAssessment $assessment, NationalQuestion $question): void
    {
        $this->questionChoiceRepository->deleteForQuestion($question);
        $this->questionRepository->delete($question);

        $this->assessmentRepository->update($assessment, [
            'total_points' => $this->assessmentRepository->sumQuestionPoints($assessment),
        ]);
    }

    private function replaceQuestionChoices(NationalQuestion $question, array $validated): array
    {
        if (in_array($question->question_type, ['multiple_choice', 'multiple_select'])) {
            $choicesData = collect($validated['choices'] ?? [])->map(function ($choice, $index) {
                return [
                    'choice_text' => $choice['choice_text'],
                    'is_correct' => (bool) $choice['is_correct'],
                    'order' => $index + 1,
                ];
            })->all();

            $this->questionChoiceRepository->replaceForQuestion($question, $choicesData);

            return collect($choicesData)->map(fn ($choice) => [
                'choice_text' => $choice['choice_text'],
                'is_correct' => $choice['is_correct'],
            ])->all();
        }

        if ($question->question_type === 'true_false') {
            $answer = (bool) ($validated['answer'] ?? true);
            $choicesData = [
                ['choice_text' => 'True', 'is_correct' => $answer === true, 'order' => 1],
                ['choice_text' => 'False', 'is_correct' => $answer === false, 'order' => 2],
            ];

            $this->questionChoiceRepository->replaceForQuestion($question, $choicesData);

            return [
                ['choice_text' => 'True', 'is_correct' => $answer === true],
                ['choice_text' => 'False', 'is_correct' => $answer === false],
            ];
        }

        $this->questionChoiceRepository->deleteForQuestion($question);

        return [];
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

    private function resolveTopicName(array $validated): ?string
    {
        if (! empty($validated['topic_id'])) {
            return Topic::find($validated['topic_id'])?->name;
        }

        return $validated['topic'] ?? null;
    }

    private function saveToQuestionBank(
        NationalQuestion $question,
        array $choicesData,
        ?string $imagePath,
        ?int $topicId,
        ?string $topicName,
        User $user
    ): void {
        if (! $topicId && $topicName) {
            $topicId = Topic::where('name', $topicName)->first()?->id;
        }

        $bankQuestion = $this->questionBankRepository->create([
            'organization_id' => null,
            'owner_type' => 'national',
            'topic_id' => $topicId,
            'created_by' => $user->id,
            'question_type' => $question->question_type,
            'question_text' => $question->question_text,
            'points' => $question->points,
            'image_path' => $imagePath,
            'is_approved' => false,
        ]);

        $this->questionBankChoiceRepository->createMany($bankQuestion, $choicesData);
        $this->questionBankStatisticRepository->initializeForQuestion($bankQuestion, 'national', null);
    }

    private function buildQuestionSignature(string $questionText, string $questionType): string
    {
        $normalizedText = preg_replace('/\s+/u', ' ', trim(strip_tags($questionText))) ?? '';

        return mb_strtolower($questionType . '|' . $normalizedText);
    }
}
