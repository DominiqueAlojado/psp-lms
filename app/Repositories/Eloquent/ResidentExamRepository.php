<?php

namespace App\Repositories\Eloquent;

use App\Models\ExamIdlePeriod;
use App\Models\ExamSessionChange;
use App\Models\Institution\InstitutionAnswer;
use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionAttempt;
use App\Models\Institution\InstitutionQuestion;
use App\Models\National\NationalAnswer;
use App\Models\National\NationalAssessment;
use App\Models\National\NationalAttempt;
use App\Models\National\NationalQuestion;
use App\Models\QuestionBank;
use App\Repositories\Contracts\ResidentExamRepositoryInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
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

    public function saveInstitutionAnswer(int $attemptId, int $questionId, array $attributes): InstitutionAnswer
    {
        $lookup = [
            'attempt_id' => $attemptId,
            'question_id' => $questionId,
        ];

        try {
            return InstitutionAnswer::updateOrCreate($lookup, $attributes);
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            $answer = InstitutionAnswer::query()
                ->where($lookup)
                ->firstOrFail();

            $answer->update($attributes);

            return $answer->fresh();
        }
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

    public function saveNationalAnswer(int $attemptId, int $questionId, array $attributes): NationalAnswer
    {
        $lookup = [
            'attempt_id' => $attemptId,
            'question_id' => $questionId,
        ];

        try {
            return NationalAnswer::updateOrCreate($lookup, $attributes);
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            $answer = NationalAnswer::query()
                ->where($lookup)
                ->firstOrFail();

            $answer->update($attributes);

            return $answer->fresh();
        }
    }

    public function createNationalAnswer(array $attributes): NationalAnswer
    {
        return NationalAnswer::create($attributes);
    }

    public function updateNationalAnswer(NationalAnswer $answer, array $attributes): bool
    {
        return $answer->update($attributes);
    }

    public function institutionQuestionBelongsToAssessment(int $questionId, int $assessmentId): bool
    {
        return InstitutionQuestion::query()
            ->whereKey($questionId)
            ->where('assessment_id', $assessmentId)
            ->exists();
    }

    public function nationalQuestionBelongsToAssessment(int $questionId, int $assessmentId): bool
    {
        return NationalQuestion::query()
            ->whereKey($questionId)
            ->where('assessment_id', $assessmentId)
            ->exists();
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
            ->withCount('questions')
            ->get();
    }

    public function getPublishedNationalExams(): Collection
    {
        return NationalAssessment::query()
            ->where('is_published', true)
            ->withCount('questions')
            ->get();
    }

    public function getCompletedInstitutionAttemptSummariesForUser(array $assessmentIds, int $userId): Collection
    {
        if ($assessmentIds === []) {
            return collect();
        }

        return InstitutionAttempt::query()
            ->selectRaw('assessment_id, COUNT(*) as attempt_count, MAX(score) as best_score, MAX(submitted_at) as last_submitted_at')
            ->where('user_id', $userId)
            ->whereIn('assessment_id', $assessmentIds)
            ->whereIn('status', ['completed', 'graded'])
            ->groupBy('assessment_id')
            ->get()
            ->keyBy('assessment_id');
    }

    public function getCompletedNationalAttemptSummariesForUser(array $assessmentIds, int $userId): Collection
    {
        if ($assessmentIds === []) {
            return collect();
        }

        return NationalAttempt::query()
            ->selectRaw('assessment_id, COUNT(*) as attempt_count, MAX(score) as best_score, MAX(submitted_at) as last_submitted_at')
            ->where('user_id', $userId)
            ->whereIn('assessment_id', $assessmentIds)
            ->whereIn('status', ['completed', 'graded'])
            ->groupBy('assessment_id')
            ->get()
            ->keyBy('assessment_id');
    }

    public function getInProgressInstitutionAssessmentIdsForUser(array $assessmentIds, int $userId): array
    {
        if ($assessmentIds === []) {
            return [];
        }

        return InstitutionAttempt::query()
            ->where('user_id', $userId)
            ->whereIn('assessment_id', $assessmentIds)
            ->where('status', 'in_progress')
            ->whereNotNull('started_at')
            ->distinct()
            ->pluck('assessment_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function getInProgressNationalAssessmentIdsForUser(array $assessmentIds, int $userId): array
    {
        if ($assessmentIds === []) {
            return [];
        }

        return NationalAttempt::query()
            ->where('user_id', $userId)
            ->whereIn('assessment_id', $assessmentIds)
            ->where('status', 'in_progress')
            ->whereNotNull('started_at')
            ->distinct()
            ->pluck('assessment_id')
            ->map(fn ($id) => (int) $id)
            ->all();
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

    public function examSessionExists(string $sessionId): bool
    {
        $table = (string) config('session.table', 'sessions');

        if ($sessionId === '' || ! DB::getSchemaBuilder()->hasTable($table)) {
            return false;
        }

        return DB::table($table)
            ->where('id', $sessionId)
            ->exists();
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

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = (string) ($exception->errorInfo[1] ?? '');

        return in_array($sqlState, ['23000', '23505'], true)
            || $driverCode === '19';
    }
}
