<?php

namespace App\Services;

use App\Actions\NationalAssessments\DuplicateNationalAssessmentAction;
use App\Models\National\NationalAssessment;
use App\Repositories\Contracts\NationalAssessmentRepositoryInterface;
use App\Services\ActivityLog\NationalAssessmentActivityLogService;
use Illuminate\Support\Facades\DB;

class NationalAssessmentDuplicationService
{
    public function __construct(
        private readonly NationalAssessmentRepositoryInterface $assessmentRepository,
        private readonly NationalAssessmentActivityLogService $activityLogService,
        private readonly DuplicateNationalAssessmentAction $duplicateAssessmentAction,
    ) {}

    public function duplicate(NationalAssessment $assessment, int $userId, ?string $title = null): NationalAssessment
    {
        $assessment = $this->assessmentRepository->loadQuestionsWithChoices($assessment);

        $duplicate = DB::transaction(fn () => $this->duplicateAssessmentAction->execute($assessment, $userId, $title));

        $this->activityLogService->logAssessmentDuplicated($assessment, $duplicate);

        return $duplicate;
    }
}
