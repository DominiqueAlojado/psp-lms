<?php

namespace App\Services;

use App\Models\National\NationalAssessment;
use App\Models\User;
use App\Repositories\Contracts\NationalAssessmentRepositoryInterface;
use App\Services\ActivityLog\NationalAssessmentActivityLogService;
use App\Traits\LogsActivity;

class NationalAssessmentManagementService
{
    use LogsActivity;

    public function __construct(
        private readonly NationalAssessmentRepositoryInterface $assessmentRepository,
        private readonly NationalAssessmentActivityLogService $activityLogService,
    ) {}

    public function create(User $user, array $validated): NationalAssessment
    {
        $assessment = $this->assessmentRepository->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'exam_year' => $validated['exam_year'],
            'exam_period' => $validated['exam_period'],
            'category' => $validated['category'],
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'total_points' => 0,
            'passing_score' => $validated['passing_score'],
            'randomize_questions' => $validated['randomize_questions'] ?? false,
            'randomize_choices' => $validated['randomize_choices'] ?? false,
            'show_results_immediately' => $validated['show_results_immediately'] ?? false,
            'allow_review' => $validated['allow_review'] ?? false,
            'is_published' => $validated['is_published'] ?? false,
            'national_ranking_enabled' => $validated['national_ranking_enabled'] ?? true,
            'institution_comparison_enabled' => $validated['institution_comparison_enabled'] ?? true,
            'scheduled_date' => $validated['scheduled_date'] ?? null,
            'results_release_date' => $validated['results_release_date'] ?? null,
            'created_by' => $user->id,
        ]);

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

        $this->withoutActivityLogging(function () use ($assessment, $validated) {
            $this->assessmentRepository->update($assessment, $validated);
        });

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

        return $this->assessmentRepository->delete($assessment);
    }
}
