<?php

namespace App\Services;

use App\Actions\InstitutionExams\DuplicateInstitutionAssessmentAction;
use App\Models\Institution\InstitutionAssessment;
use App\Repositories\Contracts\InstitutionAssessmentRepositoryInterface;

class InstitutionAssessmentDuplicationService
{
    public function __construct(
        private readonly InstitutionAssessmentRepositoryInterface $assessmentRepository,
        private readonly DuplicateInstitutionAssessmentAction $duplicateInstitutionAssessmentAction,
    ) {}

    public function duplicate(
        InstitutionAssessment $assessment,
        int $createdBy,
        ?string $title = null,
    ): InstitutionAssessment {
        $assessment = $this->assessmentRepository->loadQuestionsWithChoices($assessment);

        return $this->duplicateInstitutionAssessmentAction->execute(
            $assessment,
            $createdBy,
            $title ?? $this->generateDuplicateTitle($assessment->title, $assessment->organization_id),
        );
    }

    private function generateDuplicateTitle(string $originalTitle, int $organizationId): string
    {
        $baseTitle = $originalTitle . ' (Copy)';
        $candidate = $baseTitle;
        $suffix = 2;

        while ($this->assessmentRepository->titleExists($organizationId, $candidate)) {
            $candidate = $originalTitle . ' (Copy ' . $suffix . ')';
            $suffix++;
        }

        return $candidate;
    }
}
