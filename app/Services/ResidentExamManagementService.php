<?php

namespace App\Services;

use App\Models\Institution\InstitutionAttempt;
use App\Models\National\NationalAttempt;
use App\Models\User;
use App\Repositories\Contracts\ResidentExamRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ResidentExamManagementService
{
    public function __construct(
        private readonly ResidentExamRepositoryInterface $residentExamRepository,
        private readonly ResidentExamAttemptService $residentExamAttemptService,
    ) {}

    public function saveAnswer(Request $request, string $type, int $attemptId): array
    {
        $user = $request->user();
        $attempt = $this->residentExamAttemptService->findOwnedAttemptOrFail($type, $attemptId, $user);
        $this->residentExamAttemptService->ensureAttemptSessionAccess($request, $attempt);

        if ($attempt->status !== 'in_progress') {
            return ['error' => 'This exam has already been submitted.', 'status' => 403];
        }

        $answerData = $request->input('answer_data');
        if (is_string($answerData)) {
            $answerData = json_decode($answerData, true);
        }

        $validated = $request->validate([
            'question_id' => ['required', 'integer'],
        ]);

        $questionId = $validated['question_id'];

        if ($type === 'institution') {
            if (! $this->residentExamRepository->institutionQuestionBelongsToAssessment($questionId, $attempt->assessment_id)) {
                return ['error' => 'The selected question does not belong to this exam attempt.', 'status' => 422];
            }

            $existingAnswer = $this->residentExamRepository->findInstitutionAnswer($attempt->id, $questionId);

            if ($existingAnswer) {
                $changeCount = $existingAnswer->answer_change_count ?? 0;
                $this->logSuspiciousAnswerChanges($user, $attempt, $questionId, $changeCount);
            }

            $this->residentExamRepository->saveInstitutionAnswer($attempt->id, $questionId, [
                'answer_data' => $answerData,
                'answer_change_count' => ($existingAnswer->answer_change_count ?? 0) + 1,
            ]);

            return ['success' => true, 'status' => 200];
        }

        if (! $this->residentExamRepository->nationalQuestionBelongsToAssessment($questionId, $attempt->assessment_id)) {
            return ['error' => 'The selected question does not belong to this exam attempt.', 'status' => 422];
        }

        $existingAnswer = $this->residentExamRepository->findNationalAnswer($attempt->id, $questionId);

        if ($existingAnswer) {
            $changeCount = $existingAnswer->answer_change_count ?? 0;
            $this->logSuspiciousAnswerChanges($user, $attempt, $questionId, $changeCount);
        }

        $this->residentExamRepository->saveNationalAnswer($attempt->id, $questionId, [
            'answer_data' => $answerData,
            'answer_change_count' => ($existingAnswer->answer_change_count ?? 0) + 1,
        ]);

        return ['success' => true, 'status' => 200];
    }

    public function submit(Request $request, string $type, int $attemptId): array
    {
        $user = $request->user();
        $attempt = $this->residentExamAttemptService->findOwnedAttemptOrFail($type, $attemptId, $user);
        $this->residentExamAttemptService->ensureAttemptSessionAccess($request, $attempt);

        if ($attempt->status !== 'in_progress') {
            return ['error' => 'This exam has already been submitted.', 'status' => 422];
        }

        $attempt->load(['answers.question.choices']);
        [$validAnswers, $invalidAnswers] = $attempt->answers->partition(function ($answer) use ($attempt, $type) {
            return $answer->question !== null
                && $answer->question->assessment_id === $attempt->assessment_id;
        });

        foreach ($invalidAnswers as $answer) {
            $answer->update([
                'is_correct' => false,
                'points_earned' => 0,
            ]);
        }

        foreach ($validAnswers as $answer) {
            $answer->autoGrade();
        }

        if ($type === 'institution') {
            $this->updateInstitutionQuestionBankStatistics($attempt, $validAnswers);
        } else {
            $this->updateNationalQuestionBankStatistics($attempt, $validAnswers);
        }

        $attempt->calculateScore();
        $this->residentExamRepository->updateAttempt($attempt, [
            'submitted_at' => now(),
            'status' => 'completed',
            'active_session_id' => null,
        ]);

        return [
            'status' => 200,
            'message' => 'Exam submitted successfully! Score: ' . $attempt->percentage . '%',
        ];
    }

    public function logSessionChange(Request $request, string $type, int $attemptId): void
    {
        $user = $request->user();
        $attempt = $this->residentExamAttemptService->findOwnedAttemptOrFail($type, $attemptId, $user);
        $this->residentExamAttemptService->ensureAttemptSessionAccess($request, $attempt);

        $validated = $request->validate([
            'change_type' => ['required', 'in:ip_address,browser,both'],
            'previous_ip' => ['nullable', 'string'],
            'new_ip' => ['nullable', 'string'],
            'previous_user_agent' => ['nullable', 'string'],
            'new_user_agent' => ['nullable', 'string'],
            'browser_info' => ['nullable', 'array'],
        ]);

        $this->residentExamRepository->createSessionChange([
            'attempt_type' => $type,
            'attempt_id' => $attemptId,
            'user_id' => $user->id,
            'change_type' => $validated['change_type'],
            'previous_ip_address' => $validated['previous_ip'] ?? null,
            'new_ip_address' => $validated['new_ip'] ?? null,
            'previous_user_agent' => $validated['previous_user_agent'] ?? null,
            'new_user_agent' => $validated['new_user_agent'] ?? null,
            'browser_info' => $validated['browser_info'] ?? null,
            'detected_at' => now(),
        ]);

        if ($validated['change_type'] === 'ip_address' || $validated['change_type'] === 'both') {
            $this->residentExamRepository->incrementAttemptField($attempt, 'ip_changes_count');
        }

        if ($validated['change_type'] === 'browser' || $validated['change_type'] === 'both') {
            $this->residentExamRepository->incrementAttemptField($attempt, 'browser_changes_count');
        }
    }

    public function logActivity(Request $request, string $type, int $attemptId): void
    {
        $user = $request->user();
        $attempt = $this->residentExamAttemptService->findOwnedAttemptOrFail($type, $attemptId, $user);
        $this->residentExamAttemptService->ensureAttemptSessionAccess($request, $attempt);

        $validated = $request->validate([
            'idle_duration' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->residentExamRepository->updateAttempt($attempt, ['last_activity_at' => now()]);

        if (! isset($validated['idle_duration']) || $validated['idle_duration'] <= 0) {
            return;
        }

        $idleDuration = $validated['idle_duration'];
        $endedAt = now();
        $startedAt = $endedAt->copy()->subSeconds($idleDuration);

        $this->residentExamRepository->createIdlePeriod([
            'attempt_type' => $type,
            'attempt_id' => $attemptId,
            'user_id' => $user->id,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_seconds' => $idleDuration,
        ]);

        $this->residentExamRepository->incrementAttemptField($attempt, 'total_idle_time', $idleDuration);
        $this->residentExamRepository->incrementAttemptField($attempt, 'idle_periods_count');

        if ($idleDuration > $attempt->max_idle_duration) {
            $this->residentExamRepository->updateAttempt($attempt, ['max_idle_duration' => $idleDuration]);
        }
    }

    public function updateMetadata(Request $request, string $type, int $attemptId): array
    {
        $attempt = $this->residentExamAttemptService->findOwnedAttemptOrFail($type, $attemptId, $request->user());
        $this->residentExamAttemptService->ensureAttemptSessionAccess($request, $attempt);

        $validated = $request->validate([
            'browser_metadata' => ['required', 'array'],
            'connection_type' => ['nullable', 'string'],
            'connection_speed' => ['nullable', 'numeric'],
        ]);

        $updateData = [
            'last_activity_at' => now(),
        ];

        if (! $attempt->ip_address) {
            $updateData['ip_address'] = $request->ip();
        }

        if (! $attempt->user_agent) {
            $updateData['user_agent'] = $request->userAgent();
        }

        if (! $attempt->browser_metadata) {
            $updateData['browser_metadata'] = $validated['browser_metadata'];
            $updateData['connection_type'] = $validated['connection_type'] ?? null;
            $updateData['connection_speed'] = $validated['connection_speed'] ?? null;
        }

        $this->residentExamRepository->updateAttempt($attempt, $updateData);

        return ['success' => true];
    }

    private function updateInstitutionQuestionBankStatistics(InstitutionAttempt $attempt, iterable $answers): void
    {
        $organizationId = $attempt->organization_id;

        foreach ($answers as $answer) {
            $question = $answer->question;

            if (! $question) {
                continue;
            }

            $bankQuestion = $this->residentExamRepository->findInstitutionQuestionBankMatch(
                $question->question_text,
                $organizationId,
                $question->question_type,
                $question->topic_id
            );

            if ($bankQuestion) {
                $bankQuestion->updateStatistics(
                    $answer->is_correct ?? false,
                    null,
                    'institution',
                    $organizationId
                );

                continue;
            }

            Log::warning('Question bank entry not found for institution question', [
                'question_id' => $question->id,
                'question_text_preview' => mb_substr($question->question_text, 0, 100),
                'question_type' => $question->question_type,
                'organization_id' => $organizationId,
            ]);
        }
    }

    private function updateNationalQuestionBankStatistics(NationalAttempt $attempt, iterable $answers): void
    {
        foreach ($answers as $answer) {
            $question = $answer->question;

            if (! $question) {
                continue;
            }

            $bankQuestion = $this->residentExamRepository->findNationalQuestionBankMatch(
                $question->question_text,
                $question->question_type,
                $question->topic
            );

            if ($bankQuestion) {
                $bankQuestion->updateStatistics(
                    $answer->is_correct ?? false,
                    null,
                    'national',
                    null
                );

                continue;
            }

            Log::warning('Question bank entry not found for national question', [
                'question_id' => $question->id,
                'question_text_preview' => mb_substr($question->question_text, 0, 100),
                'question_type' => $question->question_type,
                'topic' => $question->topic,
            ]);
        }
    }

    private function logSuspiciousAnswerChanges(User $user, InstitutionAttempt|NationalAttempt $attempt, int $questionId, int $changeCount): void
    {
        if ($changeCount < 5) {
            return;
        }

        Log::warning('Suspicious answer changes detected', [
            'user_id' => $user->id,
            'attempt_id' => $attempt->id,
            'question_id' => $questionId,
            'change_count' => $changeCount + 1,
        ]);
    }
}
