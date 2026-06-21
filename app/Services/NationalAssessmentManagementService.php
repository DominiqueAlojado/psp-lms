<?php

namespace App\Services;

use App\Actions\NationalAssessments\CreateNationalAssessmentAction;
use App\Actions\NationalAssessments\DeleteNationalAssessmentAction;
use App\Actions\NationalAssessments\UpdateNationalAssessmentAction;
use App\Models\National\NationalAssessment;
use App\Models\User;
use App\Repositories\Contracts\NationalAssessmentRepositoryInterface;
use App\Services\ActivityLog\NationalAssessmentActivityLogService;

class NationalAssessmentManagementService
{
    public function __construct(
        private readonly NationalAssessmentRepositoryInterface $assessmentRepository,
        private readonly NationalAssessmentActivityLogService $activityLogService,
        private readonly CreateNationalAssessmentAction $createAssessmentAction,
        private readonly UpdateNationalAssessmentAction $updateAssessmentAction,
        private readonly DeleteNationalAssessmentAction $deleteAssessmentAction,
    ) {}

    public function create(User $user, array $validated): NationalAssessment
    {
        $assessment = $this->createAssessmentAction->execute($user, $validated);

        $this->activityLogService->logAssessmentCreated($assessment);

        return $assessment;
    }

    public function update(NationalAssessment $assessment, array $validated): void
    {
        $oldTitle = $assessment->title;
        $oldDescription = $assessment->description;
        $oldExamYear = $assessment->exam_year;
        $oldExamPeriod = $assessment->exam_period;
        $oldCategory = $assessment->category;
        $oldDurationMinutes = $assessment->duration_minutes;
        $oldPassingScore = $assessment->passing_score;
        $oldIsPublished = $assessment->is_published;
        $oldScheduledDate = $assessment->scheduled_date?->format('Y-m-d H:i:s');
        $oldResultsReleaseDate = $assessment->results_release_date?->format('Y-m-d H:i:s');
        $oldRandomizeQuestions = $assessment->randomize_questions;
        $oldRandomizeChoices = $assessment->randomize_choices;
        $oldShowResultsImmediately = $assessment->show_results_immediately;
        $oldAllowReview = $assessment->allow_review;

        $this->updateAssessmentAction->execute($assessment, $validated);

        $logData = $this->activityLogService->buildUpdateLogData(
            $assessment,
            $validated,
            $oldTitle,
            $oldDescription,
            $oldExamYear,
            $oldExamPeriod,
            $oldCategory,
            $oldDurationMinutes,
            $oldPassingScore,
            $oldIsPublished,
            $oldScheduledDate,
            $oldResultsReleaseDate,
            $oldRandomizeQuestions,
            $oldRandomizeChoices,
            $oldShowResultsImmediately,
            $oldAllowReview
        );

        if ($logData['hasChanges']) {
            $this->activityLogService->logAssessmentUpdated(
                $assessment,
                $logData['attributes'],
                $logData['oldValues']
            );
        }
    }

    public function canDelete(NationalAssessment $assessment): bool
    {
        return ! $this->assessmentRepository->attemptsExist($assessment);
    }

    public function delete(NationalAssessment $assessment): bool
    {
        $this->activityLogService->logAssessmentDeleted($assessment);

        return $this->deleteAssessmentAction->execute($assessment);
    }
}
