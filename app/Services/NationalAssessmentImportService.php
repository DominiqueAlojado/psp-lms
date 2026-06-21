<?php

namespace App\Services;

use App\Actions\NationalAssessments\ImportNationalQuestionsAction;
use App\Imports\NationalQuestionsImport;
use App\Models\National\NationalAssessment;
use App\Repositories\Contracts\NationalAssessmentRepositoryInterface;
use App\Services\ActivityLog\NationalAssessmentActivityLogService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class NationalAssessmentImportService
{
    public function __construct(
        private readonly NationalAssessmentRepositoryInterface $assessmentRepository,
        private readonly NationalAssessmentActivityLogService $activityLogService,
        private readonly ImportNationalQuestionsAction $importNationalQuestionsAction,
    ) {}

    public function importQuestions(NationalAssessment $assessment, UploadedFile $file, mixed $user): array
    {
        $import = $this->importNationalQuestionsAction->execute($assessment, $file, $user);

        $successCount = $import->getSuccessCount();
        $errors = $import->getErrors();

        $oldTotalPoints = $assessment->total_points;
        $newTotalPoints = $this->assessmentRepository->sumQuestionPoints($assessment);
        $this->assessmentRepository->update($assessment, [
            'total_points' => $newTotalPoints,
        ]);

        $assessment->refresh();
        $pointsAdded = $newTotalPoints - $oldTotalPoints;

        if (count($errors) > 0) {
            return [
                'status' => 'warning',
                'message' => $this->buildWarningMessage($successCount, $errors),
            ];
        }

        $this->activityLogService->logQuestionsImported(
            $assessment,
            $successCount,
            $pointsAdded
        );

        return [
            'status' => 'success',
            'message' => "Successfully imported {$successCount} questions!",
        ];
    }

    public function formatImportFailure(\Throwable $exception): array
    {
        Log::error('National question import failed', ['error' => $exception->getMessage()]);

        return ['file' => 'Import failed: ' . $exception->getMessage()];
    }

    private function buildWarningMessage(int $successCount, array $errors): string
    {
        $message = "Imported {$successCount} questions with " . count($errors) . ' errors: ' . implode('; ', array_slice($errors, 0, 3));

        if (count($errors) > 3) {
            $message .= '... and ' . (count($errors) - 3) . ' more errors.';
        }

        return $message;
    }
}
