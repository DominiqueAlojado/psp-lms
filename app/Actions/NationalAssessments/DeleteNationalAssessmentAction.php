<?php

namespace App\Actions\NationalAssessments;

use App\Models\National\NationalAssessment;
use App\Repositories\Contracts\NationalAssessmentRepositoryInterface;

class DeleteNationalAssessmentAction
{
    public function __construct(
        private readonly NationalAssessmentRepositoryInterface $assessmentRepository,
    ) {}

    public function execute(NationalAssessment $assessment): bool
    {
        return $this->assessmentRepository->delete($assessment);
    }
}
