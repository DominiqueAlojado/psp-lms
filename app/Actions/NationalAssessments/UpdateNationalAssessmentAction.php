<?php

namespace App\Actions\NationalAssessments;

use App\Models\National\NationalAssessment;
use App\Repositories\Contracts\NationalAssessmentRepositoryInterface;
use App\Traits\LogsActivity;

class UpdateNationalAssessmentAction
{
    use LogsActivity;

    public function __construct(
        private readonly NationalAssessmentRepositoryInterface $assessmentRepository,
    ) {}

    public function execute(NationalAssessment $assessment, array $validated): void
    {
        $this->withoutActivityLogging(function () use ($assessment, $validated) {
            $this->assessmentRepository->update($assessment, $validated);
        });
    }
}
