<?php

namespace App\Services;

use App\Models\Institution\InstitutionAssessment;
use App\Repositories\Contracts\TopicRepositoryInterface;
use App\Support\InstitutionQuestionImportRowParser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class InstitutionAssessmentQuestionPreviewService
{
    public function __construct(
        private readonly TopicRepositoryInterface $topicRepository,
        private readonly InstitutionQuestionImportRowParser $rowParser,
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
        $parsedRow = $this->rowParser->parse($data, $rowNumber);

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
            'question_text' => $parsedRow['question_text'],
            'type' => $parsedRow['type'],
            'points' => $parsedRow['points'],
            'choices' => $parsedRow['choices'],
            'explanation' => $parsedRow['explanation'],
            'topic' => $topicInfo,
        ];
    }
}
