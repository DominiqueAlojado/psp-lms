<?php

namespace App\Services;

use App\Models\Topic;
use App\Models\User;
use App\Repositories\Contracts\QuestionBankChoiceRepositoryInterface;
use App\Repositories\Contracts\QuestionBankRepositoryInterface;
use App\Repositories\Contracts\TopicRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class QuestionBankImportService
{
    public function __construct(
        private readonly QuestionBankRepositoryInterface $questionBankRepository,
        private readonly QuestionBankChoiceRepositoryInterface $questionBankChoiceRepository,
        private readonly TopicRepositoryInterface $topicRepository,
        private readonly QuestionBankReadService $questionBankReadService,
    ) {}

    public function preview(UploadedFile $file): array
    {
        $rows = Excel::toArray([], $file)[0] ?? [];

        if (empty($rows)) {
            return [
                'questions' => [],
                'errors' => [['row' => 0, 'error' => 'File is empty']],
                'total_valid' => 0,
                'total_errors' => 1,
            ];
        }

        $headers = array_shift($rows);
        $questions = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            if (empty(array_filter($row))) {
                continue;
            }

            $rowData = array_combine($headers, $row);

            try {
                $question = $this->buildPreviewQuestion($rowData);
                $questions[] = $question;
            } catch (\Exception $e) {
                $errors[] = [
                    'row' => $rowNumber,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'questions' => $questions,
            'errors' => $errors,
            'total_valid' => count($questions),
            'total_errors' => count($errors),
        ];
    }

    public function import(User $user, UploadedFile $file): array
    {
        $rows = Excel::toArray([], $file)[0] ?? [];

        if (empty($rows)) {
            return [
                'successCount' => 0,
                'errors' => ['File is empty'],
            ];
        }

        $headers = array_shift($rows);
        $successCount = 0;
        $errors = [];
        $context = $this->questionBankReadService->resolveScopeContext($user);

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            if (empty(array_filter($row))) {
                continue;
            }

            $rowData = array_combine($headers, $row);

            try {
                $topicId = $this->resolveTopicId($rowData['topic'] ?? null, $context['organizationId']);

                $question = $this->questionBankRepository->create([
                    'organization_id' => $context['organizationId'],
                    'owner_type' => $context['scope'],
                    'created_by' => $user->id,
                    'topic_id' => $topicId,
                    'question_type' => $rowData['type'] ?? 'multiple_choice',
                    'question_text' => $rowData['question_text'] ?? '',
                    'points' => (int) ($rowData['points'] ?? 1),
                    'explanation' => $rowData['explanation'] ?? null,
                    'difficulty_level' => 'medium',
                ]);

                $choices = $this->extractChoices($rowData);
                $this->questionBankChoiceRepository->createMany($question, $choices);
                $successCount++;
            } catch (\Exception $e) {
                $errors[] = "Row {$rowNumber}: {$e->getMessage()}";
            }
        }

        return [
            'successCount' => $successCount,
            'errors' => $errors,
        ];
    }

    private function buildPreviewQuestion(array $rowData): array
    {
        $question = [
            'question_text' => $rowData['question_text'] ?? '',
            'type' => $rowData['type'] ?? '',
            'points' => (int) ($rowData['points'] ?? 1),
            'topic' => $rowData['topic'] ?? '',
            'explanation' => $rowData['explanation'] ?? '',
            'choices' => [],
        ];

        foreach ($this->extractChoices($rowData) as $choice) {
            $question['choices'][] = [
                'text' => $choice['choice_text'],
                'is_correct' => $choice['is_correct'],
            ];
        }

        if ($question['question_text'] === '') {
            throw new \Exception('Question text is required');
        }

        if (empty($question['choices'])) {
            throw new \Exception('At least one choice is required');
        }

        return $question;
    }

    private function extractChoices(array $rowData): array
    {
        $choices = [];

        for ($i = 1; $i <= 6; $i++) {
            $choiceText = $rowData["choice_{$i}"] ?? '';
            $isCorrect = isset($rowData["choice_{$i}_correct_answer"])
                && in_array(strtolower((string) $rowData["choice_{$i}_correct_answer"]), ['yes', '1', 'true'], true);

            if ($choiceText !== '') {
                $choices[] = [
                    'choice_text' => $choiceText,
                    'is_correct' => $isCorrect,
                ];
            }
        }

        return $choices;
    }

    private function resolveTopicId(?string $topicName, ?int $organizationId): ?int
    {
        if ($topicName === null || trim($topicName) === '') {
            return null;
        }

        $slug = Str::slug($topicName);
        $topic = Topic::query()
            ->where('slug', $slug)
            ->where(function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId)
                    ->orWhereNull('organization_id');
            })
            ->first();

        if (! $topic) {
            $topic = $this->topicRepository->create([
                'name' => $topicName,
                'slug' => $slug . '-' . uniqid(),
                'organization_id' => $organizationId,
            ]);
        }

        return $topic->id;
    }
}
