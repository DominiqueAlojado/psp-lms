<?php

namespace App\Repositories\Eloquent;

use App\Models\ExamIdlePeriod;
use App\Models\ExamSessionChange;
use App\Models\Institution\InstitutionAnswer;
use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionAttempt;
use App\Models\National\NationalAnswer;
use App\Models\National\NationalAssessment;
use App\Models\National\NationalAttempt;
use App\Models\QuestionBank;
use App\Repositories\Contracts\ResidentExamRepositoryInterface;
use Illuminate\Support\Collection;

class ResidentExamRepository implements ResidentExamRepositoryInterface
{
    public function findInstitutionAssessmentOrFail(int $id): InstitutionAssessment
    {
        return InstitutionAssessment::findOrFail($id);
    }

    public function findNationalAssessmentOrFail(int $id): NationalAssessment
    {
        return NationalAssessment::findOrFail($id);
    }

    public function findInstitutionAttemptOrFail(int $id): InstitutionAttempt
    {
        return InstitutionAttempt::findOrFail($id);
    }

    public function findNationalAttemptOrFail(int $id): NationalAttempt
    {
        return NationalAttempt::findOrFail($id);
    }

    public function findInstitutionStartedAttempt(InstitutionAssessment $assessment, int $userId): ?InstitutionAttempt
    {
        return $assessment->attempts()
            ->where('user_id', $userId)
            ->where('status', 'in_progress')
            ->whereNotNull('started_at')
            ->first();
    }

    public function findInstitutionUnstartedAttempt(InstitutionAssessment $assessment, int $userId): ?InstitutionAttempt
    {
        return $assessment->attempts()
            ->where('user_id', $userId)
            ->where('status', 'in_progress')
            ->whereNull('started_at')
            ->first();
    }

    public function createInstitutionAttempt(InstitutionAssessment $assessment, array $attributes): InstitutionAttempt
    {
        return $assessment->attempts()->create($attributes);
    }

    public function findNationalStartedAttempt(NationalAssessment $assessment, int $userId): ?NationalAttempt
    {
        return $assessment->attempts()
            ->where('user_id', $userId)
            ->where('status', 'in_progress')
            ->whereNotNull('started_at')
            ->first();
    }

    public function findNationalUnstartedAttempt(NationalAssessment $assessment, int $userId): ?NationalAttempt
    {
        return $assessment->attempts()
            ->where('user_id', $userId)
            ->where('status', 'in_progress')
            ->whereNull('started_at')
            ->first();
    }

    public function createNationalAttempt(NationalAssessment $assessment, array $attributes): NationalAttempt
    {
        return $assessment->attempts()->create($attributes);
    }

    public function loadInstitutionQuestionsForTake(InstitutionAssessment $assessment): Collection
    {
        return $assessment->questions()
            ->with(['choices' => fn ($query) => $query->orderBy('order')])
            ->orderBy('order')
            ->get();
    }

    public function loadNationalQuestionsForTake(NationalAssessment $assessment): Collection
    {
        return $assessment->questions()
            ->with(['choices' => fn ($query) => $query->orderBy('order')])
            ->orderBy('order')
            ->get();
    }

    public function loadAttemptAnswers(InstitutionAttempt|NationalAttempt $attempt): InstitutionAttempt|NationalAttempt
    {
        return $attempt->load('answers');
    }

    public function findInstitutionAnswer(int $attemptId, int $questionId): ?InstitutionAnswer
    {
        return InstitutionAnswer::query()
            ->where('attempt_id', $attemptId)
            ->where('question_id', $questionId)
            ->first();
    }

    public function createInstitutionAnswer(array $attributes): InstitutionAnswer
    {
        return InstitutionAnswer::create($attributes);
    }

    public function updateInstitutionAnswer(InstitutionAnswer $answer, array $attributes): bool
    {
        return $answer->update($attributes);
    }

    public function findNationalAnswer(int $attemptId, int $questionId): ?NationalAnswer
    {
        return NationalAnswer::query()
            ->where('attempt_id', $attemptId)
            ->where('question_id', $questionId)
            ->first();
    }

    public function createNationalAnswer(array $attributes): NationalAnswer
    {
        return NationalAnswer::create($attributes);
    }

    public function updateNationalAnswer(NationalAnswer $answer, array $attributes): bool
    {
        return $answer->update($attributes);
    }

    public function latestCompletedInstitutionAttempt(InstitutionAssessment $assessment, int $userId): ?InstitutionAttempt
    {
        return $assessment->attempts()
            ->where('user_id', $userId)
            ->whereIn('status', ['completed', 'graded'])
            ->orderBy('submitted_at', 'desc')
            ->first();
    }

    public function bestCompletedNationalAttempt(NationalAssessment $assessment, int $userId): ?NationalAttempt
    {
        return $assessment->attempts()
            ->where('user_id', $userId)
            ->whereIn('status', ['completed', 'graded'])
            ->orderBy('score', 'desc')
            ->orderBy('submitted_at', 'desc')
            ->first();
    }

    public function loadInstitutionResultAnswers(InstitutionAttempt $attempt): Collection
    {
        return $attempt->answers()
            ->with(['question.choices' => fn ($query) => $query->orderBy('order')])
            ->orderBy('id')
            ->get();
    }

    public function loadNationalQuestionsWithChoices(NationalAssessment $assessment): Collection
    {
        return $assessment->questions()
            ->with(['choices' => fn ($query) => $query->orderBy('order')])
            ->orderBy('order')
            ->get();
    }

    public function loadNationalAnswersKeyedByQuestion(NationalAttempt $attempt): Collection
    {
        return $attempt->answers()
            ->with(['question.choices' => fn ($query) => $query->orderBy('order')])
            ->get()
            ->keyBy('question_id');
    }

    public function getPublishedInstitutionExamsForOrganization(int $organizationId): Collection
    {
        return InstitutionAssessment::query()
            ->where('organization_id', $organizationId)
            ->where('is_published', true)
            ->with(['questions'])
            ->withCount('questions')
            ->get();
    }

    public function getPublishedNationalExams(): Collection
    {
        return NationalAssessment::query()
            ->where('is_published', true)
            ->with(['questions'])
            ->withCount('questions')
            ->get();
    }

    public function hasStartedInProgressInstitutionAttempt(InstitutionAssessment $assessment, int $userId): bool
    {
        return $assessment->attempts()
            ->where('user_id', $userId)
            ->where('status', 'in_progress')
            ->whereNotNull('started_at')
            ->exists();
    }

    public function hasStartedInProgressNationalAttempt(NationalAssessment $assessment, int $userId): bool
    {
        return $assessment->attempts()
            ->where('user_id', $userId)
            ->where('status', 'in_progress')
            ->whereNotNull('started_at')
            ->exists();
    }

    public function getCompletedInstitutionAttempts(InstitutionAssessment $assessment, int $userId): Collection
    {
        return $assessment->attempts()
            ->where('user_id', $userId)
            ->whereIn('status', ['completed', 'graded'])
            ->get();
    }

    public function getCompletedNationalAttempts(NationalAssessment $assessment, int $userId): Collection
    {
        return $assessment->attempts()
            ->where('user_id', $userId)
            ->whereIn('status', ['completed', 'graded'])
            ->get();
    }

    public function createSessionChange(array $attributes): ExamSessionChange
    {
        return ExamSessionChange::create($attributes);
    }

    public function createIdlePeriod(array $attributes): ExamIdlePeriod
    {
        return ExamIdlePeriod::create($attributes);
    }

    public function updateAttempt(InstitutionAttempt|NationalAttempt $attempt, array $attributes): bool
    {
        return $attempt->update($attributes);
    }

    public function incrementAttemptField(InstitutionAttempt|NationalAttempt $attempt, string $field, int|float $amount = 1): void
    {
        $attempt->increment($field, $amount);
    }

    public function findInstitutionQuestionBankMatch(string $questionText, int $organizationId, string $questionType, ?int $topicId): ?QuestionBank
    {
        $bankQuestion = QuestionBank::query()
            ->where('question_text', $questionText)
            ->where('owner_type', 'institution')
            ->where('organization_id', $organizationId)
            ->first();

        if (! $bankQuestion && $topicId) {
            $bankQuestion = QuestionBank::query()
                ->where('owner_type', 'institution')
                ->where('organization_id', $organizationId)
                ->where('question_type', $questionType)
                ->where('topic_id', $topicId)
                ->first();
        }

        if (! $bankQuestion) {
            $questionStart = mb_substr(trim($questionText), 0, 50);
            $bankQuestion = QuestionBank::query()
                ->where('owner_type', 'institution')
                ->where('organization_id', $organizationId)
                ->where('question_type', $questionType)
                ->whereRaw('SUBSTRING(TRIM(question_text), 1, 50) = ?', [$questionStart])
                ->first();
        }

        return $bankQuestion;
    }

    public function findNationalQuestionBankMatch(string $questionText, string $questionType, ?string $topic): ?QuestionBank
    {
        $bankQuestion = QuestionBank::query()
            ->where('question_text', $questionText)
            ->where('owner_type', 'national')
            ->first();

        if (! $bankQuestion && $topic) {
            $bankQuestion = QuestionBank::query()
                ->where('owner_type', 'national')
                ->where('question_type', $questionType)
                ->whereHas('topic', function ($query) use ($topic) {
                    $query->where('name', 'like', '%' . $topic . '%');
                })
                ->first();
        }

        if (! $bankQuestion) {
            $questionStart = mb_substr(trim($questionText), 0, 50);
            $bankQuestion = QuestionBank::query()
                ->where('owner_type', 'national')
                ->where('question_type', $questionType)
                ->whereRaw('SUBSTRING(TRIM(question_text), 1, 50) = ?', [$questionStart])
                ->first();
        }

        return $bankQuestion;
    }
}
