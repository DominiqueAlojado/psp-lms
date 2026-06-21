<?php

namespace App\Services;

use App\Actions\InstitutionExams\ImportInstitutionAssessmentQuestionsAction;
use App\Models\Institution\InstitutionAssessment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class InstitutionAssessmentImportService
{
    public function __construct(
        private readonly ImportInstitutionAssessmentQuestionsAction $importQuestionsAction,
    ) {}

    /**
     * @return array{status:'success'|'warning',message:string}
     */
    public function importQuestions(
        InstitutionAssessment $assessment,
        UploadedFile $file,
        User $user,
    ): array {
        $result = $this->importQuestionsAction->execute($assessment, $file, $user);
        $successCount = $result['success_count'];
        $errors = $result['errors'];

        if (count($errors) > 0) {
            $message = "Imported {$successCount} questions with " . count($errors) . ' errors: ' . implode('; ', array_slice($errors, 0, 3));
            if (count($errors) > 3) {
                $message .= '... and ' . (count($errors) - 3) . ' more errors.';
            }

            return [
                'status' => 'warning',
                'message' => $message,
            ];
        }

        return [
            'status' => 'success',
            'message' => "Successfully imported {$successCount} questions!",
        ];
    }

    /**
     * @return array{file:string}
     */
    public function formatImportFailure(\Throwable $exception): array
    {
        Log::error('Question import failed', ['error' => $exception->getMessage()]);

        return [
            'file' => 'Import failed: ' . $exception->getMessage(),
        ];
    }
}
