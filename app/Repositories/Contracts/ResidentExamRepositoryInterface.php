<?php

namespace App\Repositories\Contracts;

use App\Models\ExamIdlePeriod;
use App\Models\ExamSessionChange;
use App\Models\Institution\InstitutionAnswer;
use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionAttempt;
use App\Models\National\NationalAnswer;
use App\Models\National\NationalAssessment;
use App\Models\National\NationalAttempt;
use App\Models\QuestionBank;
use Illuminate\Support\Collection;

interface ResidentExamRepositoryInterface
{
    public function findInstitutionAssessmentOrFail(int $id): InstitutionAssessment;

    public function findNationalAssessmentOrFail(int $id): NationalAssessment;

    public function findInstitutionAttemptOrFail(int $id): InstitutionAttempt;

    public function findNationalAttemptOrFail(int $id): NationalAttempt;

    public function findInstitutionStartedAttempt(InstitutionAssessment $assessment, int $userId): ?InstitutionAttempt;

    public function findInstitutionUnstartedAttempt(InstitutionAssessment $assessment, int $userId): ?InstitutionAttempt;

    public function createInstitutionAttempt(InstitutionAssessment $assessment, array $attributes): InstitutionAttempt;

    public function findNationalStartedAttempt(NationalAssessment $assessment, int $userId): ?NationalAttempt;

    public function findNationalUnstartedAttempt(NationalAssessment $assessment, int $userId): ?NationalAttempt;

    public function createNationalAttempt(NationalAssessment $assessment, array $attributes): NationalAttempt;

    public function loadInstitutionQuestionsForTake(InstitutionAssessment $assessment): Collection;

    public function loadNationalQuestionsForTake(NationalAssessment $assessment): Collection;

    public function loadAttemptAnswers(InstitutionAttempt|NationalAttempt $attempt): InstitutionAttempt|NationalAttempt;

    public function findInstitutionAnswer(int $attemptId, int $questionId): ?InstitutionAnswer;

    public function saveInstitutionAnswer(int $attemptId, int $questionId, array $attributes): InstitutionAnswer;

    public function createInstitutionAnswer(array $attributes): InstitutionAnswer;

    public function updateInstitutionAnswer(InstitutionAnswer $answer, array $attributes): bool;

    public function findNationalAnswer(int $attemptId, int $questionId): ?NationalAnswer;

    public function saveNationalAnswer(int $attemptId, int $questionId, array $attributes): NationalAnswer;

    public function createNationalAnswer(array $attributes): NationalAnswer;

    public function updateNationalAnswer(NationalAnswer $answer, array $attributes): bool;

    public function institutionQuestionBelongsToAssessment(int $questionId, int $assessmentId): bool;

    public function nationalQuestionBelongsToAssessment(int $questionId, int $assessmentId): bool;

    public function latestCompletedInstitutionAttempt(InstitutionAssessment $assessment, int $userId): ?InstitutionAttempt;

    public function bestCompletedNationalAttempt(NationalAssessment $assessment, int $userId): ?NationalAttempt;

    public function loadInstitutionResultAnswers(InstitutionAttempt $attempt): Collection;

    public function loadNationalQuestionsWithChoices(NationalAssessment $assessment): Collection;

    public function loadNationalAnswersKeyedByQuestion(NationalAttempt $attempt): Collection;

    public function getPublishedInstitutionExamsForOrganization(int $organizationId): Collection;

    public function getPublishedNationalExams(): Collection;

    public function getCompletedInstitutionAttemptSummariesForUser(array $assessmentIds, int $userId): Collection;

    public function getCompletedNationalAttemptSummariesForUser(array $assessmentIds, int $userId): Collection;

    public function getInProgressInstitutionAssessmentIdsForUser(array $assessmentIds, int $userId): array;

    public function getInProgressNationalAssessmentIdsForUser(array $assessmentIds, int $userId): array;

    public function hasStartedInProgressInstitutionAttempt(InstitutionAssessment $assessment, int $userId): bool;

    public function hasStartedInProgressNationalAttempt(NationalAssessment $assessment, int $userId): bool;

    public function getCompletedInstitutionAttempts(InstitutionAssessment $assessment, int $userId): Collection;

    public function getCompletedNationalAttempts(NationalAssessment $assessment, int $userId): Collection;

    public function createSessionChange(array $attributes): ExamSessionChange;

    public function createIdlePeriod(array $attributes): ExamIdlePeriod;

    public function updateAttempt(InstitutionAttempt|NationalAttempt $attempt, array $attributes): bool;

    public function incrementAttemptField(InstitutionAttempt|NationalAttempt $attempt, string $field, int|float $amount = 1): void;

    public function examSessionExists(string $sessionId): bool;

    public function findInstitutionQuestionBankMatch(string $questionText, int $organizationId, string $questionType, ?int $topicId): ?QuestionBank;

    public function findNationalQuestionBankMatch(string $questionText, string $questionType, ?string $topic): ?QuestionBank;
}
