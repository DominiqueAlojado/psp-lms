<?php

namespace App\Services;

use App\Models\Institution\InstitutionAssessment;
use App\Models\National\NationalAssessment;
use App\Models\User;
use App\Repositories\Contracts\ResidentExamRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ResidentExamReadService
{
    public function __construct(
        private readonly ResidentExamRepositoryInterface $residentExamRepository,
        private readonly ResidentExamAttemptService $residentExamAttemptService,
    ) {}

    public function takePayload(Request $request, string $type, int $id): array
    {
        $user = $request->user();

        if ($type === 'institution') {
            $assessment = $this->residentExamRepository->findInstitutionAssessmentOrFail($id);

            if ($assessment->organization_id !== $user->current_organization_id) {
                abort(403, 'You do not have access to this exam.');
            }

            if (! $assessment->isAvailable()) {
                abort(403, 'This exam is not currently available.');
            }

            $attempt = $this->residentExamAttemptService->startOrReuseInstitutionAttempt($request, $assessment, $user);
            $questions = $this->residentExamRepository->loadInstitutionQuestionsForTake($assessment);

            return $this->buildTakePayload('institution', $assessment, $attempt, $questions);
        }

        if ($type === 'inservice') {
            $assessment = $this->residentExamRepository->findNationalAssessmentOrFail($id);

            if (! $assessment->isAvailable()) {
                abort(403, 'This exam is not currently available.');
            }

            $attempt = $this->residentExamAttemptService->startOrReuseNationalAttempt($request, $assessment, $user);
            $questions = $this->residentExamRepository->loadNationalQuestionsForTake($assessment);

            return $this->buildTakePayload('inservice', $assessment, $attempt, $questions);
        }

        abort(404);
    }

    public function resultsPagePayload(User $user, string $type, int $id): array
    {
        return $this->buildResultsPayload($user, $type, $id);
    }

    public function resultsApiPayload(User $user, string $type, int $id): array
    {
        return $this->buildResultsPayload($user, $type, $id, true);
    }

    public function indexPayload(User $user): array
    {
        $currentOrganization = $user->currentOrganization;
        $organizationId = $user->current_organization_id;
        $isNational = $currentOrganization?->type === 'national';

        $availableExams = [];
        $upcomingExams = [];
        $completedExams = [];

        $institutionExams = $isNational
            ? collect()
            : $this->residentExamRepository->getPublishedInstitutionExamsForOrganization($organizationId);

        $institutionAttemptSummaries = $this->residentExamRepository->getCompletedInstitutionAttemptSummariesForUser(
            $institutionExams->pluck('id')->all(),
            $user->id,
        );
        $institutionInProgressIds = array_flip($this->residentExamRepository->getInProgressInstitutionAssessmentIdsForUser(
            $institutionExams->pluck('id')->all(),
            $user->id,
        ));

        foreach ($institutionExams as $exam) {
            $attemptSummary = $institutionAttemptSummaries->get($exam->id);
            $attemptCount = (int) ($attemptSummary->attempt_count ?? 0);
            $bestScore = $attemptSummary?->best_score;
            $lastSubmittedAt = $attemptSummary?->last_submitted_at;
            $examData = [
                'id' => $exam->id,
                'title' => $exam->title,
                'description' => $exam->description,
                'exam_category' => $exam->exam_category,
                'type' => 'institution',
                'questions_count' => $exam->questions_count,
                'total_points' => $exam->total_points,
                'passing_score' => $exam->passing_score,
                'duration_minutes' => $exam->duration_minutes,
                'is_available' => $exam->isAvailable(),
                'available_from' => $exam->available_from?->format('M d, Y h:i A'),
                'available_until' => $exam->available_until?->format('M d, Y h:i A'),
                'attempt_count' => $attemptCount,
                'max_attempts' => null,
                'best_score' => $bestScore ? round(($bestScore / $exam->total_points) * 100, 2) : null,
                'last_attempted' => $lastSubmittedAt ? \Illuminate\Support\Carbon::parse($lastSubmittedAt)->diffForHumans() : null,
                'has_in_progress_attempt' => isset($institutionInProgressIds[$exam->id]),
            ];

            $this->bucketExam($examData, $availableExams, $upcomingExams, $completedExams, $exam->isAvailable(), $exam->available_from, $attemptCount);
        }

        $nationalExams = $isNational
            ? $this->residentExamRepository->getPublishedNationalExams()
            : collect();

        $nationalAttemptSummaries = $this->residentExamRepository->getCompletedNationalAttemptSummariesForUser(
            $nationalExams->pluck('id')->all(),
            $user->id,
        );
        $nationalInProgressIds = array_flip($this->residentExamRepository->getInProgressNationalAssessmentIdsForUser(
            $nationalExams->pluck('id')->all(),
            $user->id,
        ));

        foreach ($nationalExams as $exam) {
            $attemptSummary = $nationalAttemptSummaries->get($exam->id);
            $attemptCount = (int) ($attemptSummary->attempt_count ?? 0);
            $bestScore = $attemptSummary?->best_score;
            $lastSubmittedAt = $attemptSummary?->last_submitted_at;
            $examData = [
                'id' => $exam->id,
                'title' => $exam->title,
                'description' => $exam->description,
                'type' => 'inservice',
                'questions_count' => $exam->questions_count,
                'total_points' => $exam->total_points,
                'passing_score' => $exam->passing_score,
                'duration_minutes' => $exam->duration_minutes,
                'is_available' => $exam->isAvailable(),
                'available_from' => $exam->scheduled_date?->format('M d, Y h:i A'),
                'available_until' => null,
                'attempt_count' => $attemptCount,
                'max_attempts' => null,
                'best_score' => $bestScore ? round(($bestScore / $exam->total_points) * 100, 2) : null,
                'last_attempted' => $lastSubmittedAt ? \Illuminate\Support\Carbon::parse($lastSubmittedAt)->diffForHumans() : null,
                'has_in_progress_attempt' => isset($nationalInProgressIds[$exam->id]),
            ];

            $this->bucketExam($examData, $availableExams, $upcomingExams, $completedExams, $exam->isAvailable(), $exam->scheduled_date, $attemptCount);
        }

        return [
            'availableExams' => $availableExams,
            'completedExams' => $completedExams,
            'upcomingExams' => $upcomingExams,
        ];
    }

    public function currentIpPayload(Request $request, string $type, int $attemptId): array
    {
        $attempt = $this->residentExamAttemptService->findOwnedAttemptOrFail($type, $attemptId, $request->user());
        $this->residentExamAttemptService->ensureAttemptSessionAccess($request, $attempt);

        return ['ip_address' => $request->ip()];
    }

    public function sessionInfoPayload(Request $request, string $type, int $attemptId): array
    {
        $attempt = $this->residentExamAttemptService->findOwnedAttemptOrFail($type, $attemptId, $request->user());
        $this->residentExamAttemptService->ensureAttemptSessionAccess($request, $attempt);

        return [
            'user_agent' => $attempt->user_agent,
            'ip_address' => $attempt->ip_address,
            'browser_metadata' => $attempt->browser_metadata,
        ];
    }

    private function buildTakePayload(string $type, InstitutionAssessment|NationalAssessment $assessment, $attempt, Collection $questions): array
    {
        if ($assessment->randomize_questions) {
            $questions = $questions->shuffle($attempt->id);
        }

        $publicDisk = Storage::disk('public');

        $attempt = $this->residentExamRepository->loadAttemptAnswers($attempt);
        $validChoiceIds = $questions->mapWithKeys(fn ($question) => [$question->id => $question->choices->pluck('id')->toArray()]);

        $savedAnswers = $attempt->answers->mapWithKeys(function ($answer) use ($validChoiceIds) {
            $answerData = $answer->answer_data;

            if (isset($answerData['choice_id']) && ! in_array($answerData['choice_id'], $validChoiceIds[$answer->question_id] ?? [], true)) {
                return [$answer->question_id => []];
            }

            if (isset($answerData['choice_ids'])) {
                $validIds = array_intersect($answerData['choice_ids'], $validChoiceIds[$answer->question_id] ?? []);
                if (empty($validIds)) {
                    return [$answer->question_id => []];
                }
                $answerData['choice_ids'] = array_values($validIds);
            }

            return [$answer->question_id => $answerData];
        })->filter();

        return [
            'exam' => [
                'id' => $assessment->id,
                'type' => $type,
                'title' => $assessment->title,
                'description' => $assessment->description,
                'duration_minutes' => $assessment->duration_minutes,
                'total_points' => $assessment->total_points,
                'passing_score' => $assessment->passing_score,
                'randomize_questions' => $assessment->randomize_questions,
                'randomize_choices' => $assessment->randomize_choices,
                'questions' => $questions->map(function ($q) use ($assessment, $attempt) {
                    $choices = $q->choices;
                    if ($assessment->randomize_choices) {
                        $choices = $choices->shuffle($attempt->id + $q->id);
                    }

                    return [
                        'id' => $q->id,
                        'question_type' => $q->question_type,
                        'question_text' => $q->question_text,
                        'points' => $q->points,
                        'image_url' => $q->image_path ? $publicDisk->url($q->image_path) : null,
                        'choices' => $choices->map(fn ($c) => [
                            'id' => $c->id,
                            'choice_text' => $c->choice_text,
                        ])->values(),
                    ];
                })->values(),
            ],
            'attempt' => [
                'id' => $attempt->id,
                'started_at' => $attempt->started_at->toIso8601String(),
            ],
            'savedAnswers' => $savedAnswers,
        ];
    }

    private function buildResultsPayload(User $user, string $type, int $id, bool $json = false): array
    {
        if ($type === 'institution') {
            $assessment = $this->residentExamRepository->findInstitutionAssessmentOrFail($id);

            if ($assessment->organization_id !== $user->current_organization_id) {
                abort(403, 'You do not have access to this exam.');
            }

            $attempt = $this->residentExamRepository->latestCompletedInstitutionAttempt($assessment, $user->id);
            if (! $attempt) {
                if ($json) {
                    abort(response()->json(['error' => 'No completed attempts found'], 404));
                }
                abort(302, '', ['Location' => '/resident-exams']);
            }

            $answers = $this->residentExamRepository->loadInstitutionResultAnswers($attempt);
            $publicDisk = Storage::disk('public');
            $questionsData = $answers->map(function ($answer) use ($publicDisk) {
                $question = $answer->question;
                $selectedChoiceIds = [];

                if (isset($answer->answer_data['choice_id'])) {
                    $selectedChoiceIds = [$answer->answer_data['choice_id']];
                } elseif (isset($answer->answer_data['choice_ids'])) {
                    $selectedChoiceIds = $answer->answer_data['choice_ids'];
                }

                return [
                    'id' => $question->id,
                    'question_type' => $question->question_type,
                    'question_text' => $question->question_text,
                    'points' => $question->points,
                    'explanation' => $question->explanation,
                    'image_url' => $question->image_path ? $publicDisk->url($question->image_path) : null,
                    'order' => $question->order,
                    'choices' => $question->choices->map(fn ($choice) => [
                        'id' => $choice->id,
                        'choice_text' => $choice->choice_text,
                        'is_correct' => $choice->is_correct,
                        'order' => $choice->order,
                    ]),
                    'selected_choice_ids' => $selectedChoiceIds,
                    'is_correct' => $answer->is_correct,
                    'points_earned' => $answer->is_correct ? $question->points : 0,
                ];
            });

            return $this->resultPayloadArray($assessment, 'institution', $attempt, $questionsData);
        }

        if ($type === 'inservice') {
            $assessment = $this->residentExamRepository->findNationalAssessmentOrFail($id);
            $attempt = $this->residentExamRepository->bestCompletedNationalAttempt($assessment, $user->id);

            if (! $attempt) {
                if ($json) {
                    abort(response()->json(['error' => 'No completed attempts found'], 404));
                }
                abort(302, '', ['Location' => '/resident-exams']);
            }

            $questions = $this->residentExamRepository->loadNationalQuestionsWithChoices($assessment);
            $answers = $this->residentExamRepository->loadNationalAnswersKeyedByQuestion($attempt);
            $publicDisk = Storage::disk('public');

            $questionsData = $questions->map(function ($question) use ($answers, $publicDisk) {
                $answer = $answers->get($question->id);
                $selectedChoiceIds = [];

                if ($answer) {
                    if (isset($answer->answer_data['choice_id'])) {
                        $selectedChoiceIds = [$answer->answer_data['choice_id']];
                    } elseif (isset($answer->answer_data['choice_ids'])) {
                        $selectedChoiceIds = $answer->answer_data['choice_ids'];
                    }
                }

                return [
                    'id' => $question->id,
                    'question_type' => $question->question_type,
                    'question_text' => $question->question_text,
                    'points' => $question->points,
                    'explanation' => $question->explanation,
                    'image_url' => $question->image_path ? $publicDisk->url($question->image_path) : null,
                    'order' => $question->order,
                    'choices' => $question->choices->map(fn ($choice) => [
                        'id' => $choice->id,
                        'choice_text' => $choice->choice_text,
                        'is_correct' => $choice->is_correct,
                        'order' => $choice->order,
                    ]),
                    'selected_choice_ids' => $selectedChoiceIds,
                    'is_correct' => $answer?->is_correct ?? false,
                    'points_earned' => ($answer?->is_correct ?? false) ? $question->points : 0,
                ];
            });

            return $this->resultPayloadArray($assessment, 'inservice', $attempt, $questionsData);
        }

        abort(404);
    }

    private function resultPayloadArray(InstitutionAssessment|NationalAssessment $assessment, string $type, $attempt, Collection $questionsData): array
    {
        return [
            'exam' => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'description' => $assessment->description,
                'type' => $type,
                'total_points' => $assessment->total_points,
                'passing_score' => $assessment->passing_score,
                'questions' => $questionsData,
            ],
            'attempt' => [
                'id' => $attempt->id,
                'score' => $attempt->score,
                'percentage' => $attempt->percentage,
                'started_at' => $attempt->started_at?->format('M d, Y h:i A'),
                'submitted_at' => $attempt->submitted_at?->format('M d, Y h:i A'),
                'time_taken_minutes' => $attempt->started_at && $attempt->submitted_at
                    ? $attempt->started_at->diffInMinutes($attempt->submitted_at)
                    : null,
            ],
        ];
    }

    private function bucketExam(array $examData, array &$availableExams, array &$upcomingExams, array &$completedExams, bool $isAvailable, $startsAt, int $attemptCount): void
    {
        if ($isAvailable) {
            $availableExams[] = $examData;
            return;
        }

        if ($startsAt && now()->isBefore($startsAt)) {
            $upcomingExams[] = $examData;
            return;
        }

        if ($attemptCount > 0) {
            $completedExams[] = $examData;
        }
    }
}
