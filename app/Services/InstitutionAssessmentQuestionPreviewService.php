<?php

namespace App\Services;

use App\Models\Institution\InstitutionAssessment;
use App\Repositories\Contracts\TopicRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class InstitutionAssessmentQuestionPreviewService
{
    public function __construct(
        private readonly TopicRepositoryInterface $topicRepository,
    ) {}

    public function preview(InstitutionAssessment $assessment, UploadedFile $file): array
    {
        $rows = Excel::toArray([], $file)[0] ?? [];

        if (empty($rows)) {
            return [
                'success' => true,
                'questions' => [],
                'errors' => [],
                'total_valid' => 0,
                'total_errors' => 0,
            ];
        }

        $header = array_shift($rows);
        $parsedQuestions = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            if (empty(array_filter($row, fn ($value) => $value !== null && $value !== ''))) {
                continue;
            }

            $data = array_combine($header, $row);

            try {
                $parsedQuestions[] = $this->buildPreviewQuestion($assessment, $data, $rowNumber);
            } catch (\RuntimeException $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        return [
            'success' => true,
            'questions' => $parsedQuestions,
            'errors' => $errors,
            'total_valid' => count($parsedQuestions),
            'total_errors' => count($errors),
        ];
    }

    private function buildPreviewQuestion(InstitutionAssessment $assessment, array $data, int $rowNumber): array
    {
        if (empty($data['question_text']) || empty($data['type']) || empty($data['points'])) {
            throw new \RuntimeException("Row {$rowNumber}: Missing required fields");
        }

        $type = strtolower(trim((string) $data['type']));
        if (! in_array($type, ['multiple_choice', 'multiple_select', 'true_false'], true)) {
            throw new \RuntimeException("Row {$rowNumber}: Invalid type '{$data['type']}'");
        }

        $choices = $this->buildChoices($type, $data, $rowNumber);

        $topicInfo = null;
        if (! empty($data['topic_optional'])) {
            $topicName = trim((string) $data['topic_optional']);
            $existingTopic = $this->topicRepository->findBySlugForOrganizationWithGlobals(
                Str::slug($topicName),
                $assessment->organization_id
            );

            $topicInfo = [
                'name' => $topicName,
                'exists' => $existingTopic !== null,
                'will_create' => $existingTopic === null,
            ];
        }

        return [
            'row_number' => $rowNumber,
            'question_text' => trim((string) $data['question_text']),
            'type' => $type,
            'points' => (int) $data['points'],
            'choices' => $choices,
            'explanation' => ! empty($data['explanation_optional']) ? trim((string) $data['explanation_optional']) : null,
            'topic' => $topicInfo,
        ];
    }

    private function buildChoices(string $type, array $data, int $rowNumber): array
    {
        $choice1 = trim((string) ($data['choice_1_correct_answer'] ?? ''));
        $choice2 = trim((string) ($data['choice_2'] ?? ''));
        $choice3 = trim((string) ($data['choice_3'] ?? ''));
        $choice4 = trim((string) ($data['choice_4'] ?? ''));

        if ($type === 'true_false') {
            return [
                ['text' => 'True', 'is_correct' => strtolower($choice1) === 'true'],
                ['text' => 'False', 'is_correct' => strtolower($choice1) !== 'true'],
            ];
        }

        if ($choice1 === '' || $choice2 === '') {
            throw new \RuntimeException("Row {$rowNumber}: At least 2 choices required");
        }

        $choices = [
            ['text' => $choice1, 'is_correct' => true],
            ['text' => $choice2, 'is_correct' => false],
        ];

        if ($choice3 !== '') {
            $choices[] = ['text' => $choice3, 'is_correct' => false];
        }

        if ($choice4 !== '') {
            $choices[] = ['text' => $choice4, 'is_correct' => false];
        }

        return $choices;
    }
}
