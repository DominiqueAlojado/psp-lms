<?php

namespace App\Http\Controllers;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionAttempt;
use App\Models\National\NationalAssessment;
use App\Models\National\NationalAttempt;
use App\Repositories\Contracts\ResidentExamRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ResidentExamController extends Controller
{
    private const EXAM_SESSION_CONFLICT_MESSAGE = 'This exam is already active in another browser or device.';

    public function __construct(
        private readonly ResidentExamRepositoryInterface $residentExamRepository,
    ) {}

    /**
     * Show the exam taking page (start or resume).
     */
    public function take(Request $request, string $type, int $id): Response
    {
        $user = $request->user();

        if ($type === 'institution') {
            $assessment = $this->residentExamRepository->findInstitutionAssessmentOrFail($id);

            // Verify user has access (same organization)
            if ($assessment->organization_id !== $user->current_organization_id) {
                abort(403, 'You do not have access to this exam.');
            }

            // Check if exam is available
            if (! $assessment->isAvailable()) {
                abort(403, 'This exam is not currently available.');
            }

            // Check for existing in-progress attempt (must have started_at to be considered in progress)
            $attempt = $this->residentExamRepository->findInstitutionStartedAttempt($assessment, $user->id);

            // If no in-progress attempt, check for an unstarted attempt (from seeder) and reuse it
            if (! $attempt) {
                $unstartedAttempt = $this->residentExamRepository->findInstitutionUnstartedAttempt($assessment, $user->id);

                if ($unstartedAttempt) {
                    // Reuse the unstarted attempt and mark it as started
                    $this->residentExamRepository->updateAttempt($unstartedAttempt, [
                        'started_at' => now(),
                        'active_session_id' => $request->session()->getId(),
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'last_activity_at' => now(),
                    ]);
                    $attempt = $unstartedAttempt;
                } else {
                    // Create a new attempt
                    $attempt = $this->residentExamRepository->createInstitutionAttempt($assessment, [
                        'user_id' => $user->id,
                        'year_level' => $user->resident?->year_level,
                        'organization_id' => $user->current_organization_id,
                        'started_at' => now(),
                        'total_points' => $assessment->total_points,
                        'status' => 'in_progress',
                        'active_session_id' => $request->session()->getId(),
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'last_activity_at' => now(),
                    ]);
                }
            }

            $this->claimOrAbortAttemptSession($request, $attempt);

            // Load questions with choices in proper order
            $questions = $this->residentExamRepository->loadInstitutionQuestionsForTake($assessment);

            // Apply randomization if enabled (use attempt ID as seed for consistency)
            if ($assessment->randomize_questions) {
                $questions = $questions->shuffle($attempt->id);
            }

            // Load existing answers and validate against current choices
            $attempt = $this->residentExamRepository->loadAttemptAnswers($attempt);

            // Create a map of valid choice IDs per question
            $validChoiceIds = $questions->mapWithKeys(function ($question) {
                return [$question->id => $question->choices->pluck('id')->toArray()];
            });

            $savedAnswers = $attempt->answers->mapWithKeys(function ($answer) use ($validChoiceIds) {
                $answerData = $answer->answer_data;

                // Validate and clean choice IDs
                if (isset($answerData['choice_id'])) {
                    // Single choice - check if it still exists
                    if (! in_array($answerData['choice_id'], $validChoiceIds[$answer->question_id] ?? [])) {
                        // Choice no longer exists, remove the answer
                        return [$answer->question_id => []];
                    }
                } elseif (isset($answerData['choice_ids'])) {
                    // Multiple choices - filter out invalid ones
                    $validIds = array_intersect(
                        $answerData['choice_ids'],
                        $validChoiceIds[$answer->question_id] ?? []
                    );

                    if (empty($validIds)) {
                        // No valid choices left, remove the answer
                        return [$answer->question_id => []];
                    }

                    // Update with only valid choice IDs
                    $answerData['choice_ids'] = array_values($validIds);
                }

                return [$answer->question_id => $answerData];
            })->filter();

            return Inertia::render('resident-exams/take', [
                'exam' => [
                    'id' => $assessment->id,
                    'type' => 'institution',
                    'title' => $assessment->title,
                    'description' => $assessment->description,
                    'duration_minutes' => $assessment->duration_minutes,
                    'total_points' => $assessment->total_points,
                    'passing_score' => $assessment->passing_score,
                    'randomize_questions' => $assessment->randomize_questions,
                    'randomize_choices' => $assessment->randomize_choices,
                    'questions' => $questions->map(function ($q) use ($assessment, $attempt) {
                        $choices = $q->choices;

                        // Randomize choices if enabled (use attempt ID + question ID as seed)
                        if ($assessment->randomize_choices) {
                            $choices = $choices->shuffle($attempt->id + $q->id);
                        }

                        return [
                            'id' => $q->id,
                            'question_type' => $q->question_type,
                            'question_text' => $q->question_text,
                            'points' => $q->points,
                            'image_url' => $q->image_path ? Storage::disk('public')->url($q->image_path) : null,
                            'choices' => $choices->map(fn($c) => [
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
            ]);
        } elseif ($type === 'inservice') {
            $assessment = $this->residentExamRepository->findNationalAssessmentOrFail($id);

            // Check if exam is available
            if (! $assessment->isAvailable()) {
                abort(403, 'This exam is not currently available.');
            }

            // Check for existing in-progress attempt (must have started_at to be considered in progress)
            $attempt = $this->residentExamRepository->findNationalStartedAttempt($assessment, $user->id);

            // If no in-progress attempt, check for an unstarted attempt (from seeder) and reuse it
            if (! $attempt) {
                $unstartedAttempt = $this->residentExamRepository->findNationalUnstartedAttempt($assessment, $user->id);

                if ($unstartedAttempt) {
                    // Reuse the unstarted attempt and mark it as started
                    $this->residentExamRepository->updateAttempt($unstartedAttempt, [
                        'started_at' => now(),
                        'active_session_id' => $request->session()->getId(),
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'browser_metadata' => $request->input('browser_metadata'),
                        'connection_type' => $request->input('connection_type'),
                        'connection_speed' => $request->input('connection_speed'),
                        'last_activity_at' => now(),
                    ]);
                    $attempt = $unstartedAttempt;
                } else {
                    // Create a new attempt
                    $attempt = $this->residentExamRepository->createNationalAttempt($assessment, [
                        'user_id' => $user->id,
                        'year_level' => $user->resident?->year_level,
                        'organization_id' => $user->current_organization_id,
                        'started_at' => now(),
                        'total_points' => $assessment->total_points,
                        'status' => 'in_progress',
                        'active_session_id' => $request->session()->getId(),
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'browser_metadata' => $request->input('browser_metadata'),
                        'connection_type' => $request->input('connection_type'),
                        'connection_speed' => $request->input('connection_speed'),
                        'last_activity_at' => now(),
                    ]);
                }
            }

            $this->claimOrAbortAttemptSession($request, $attempt);

            // Load questions with choices in proper order
            $questions = $this->residentExamRepository->loadNationalQuestionsForTake($assessment);

            // Apply randomization if enabled (use attempt ID as seed for consistency)
            if ($assessment->randomize_questions) {
                $questions = $questions->shuffle($attempt->id);
            }

            // Load existing answers and validate against current choices
            $attempt = $this->residentExamRepository->loadAttemptAnswers($attempt);

            // Create a map of valid choice IDs per question
            $validChoiceIds = $questions->mapWithKeys(function ($question) {
                return [$question->id => $question->choices->pluck('id')->toArray()];
            });

            $savedAnswers = $attempt->answers->mapWithKeys(function ($answer) use ($validChoiceIds) {
                $answerData = $answer->answer_data;

                // Validate and clean choice IDs
                if (isset($answerData['choice_id'])) {
                    // Single choice - check if it still exists
                    if (! in_array($answerData['choice_id'], $validChoiceIds[$answer->question_id] ?? [])) {
                        // Choice no longer exists, remove the answer
                        return [$answer->question_id => []];
                    }
                } elseif (isset($answerData['choice_ids'])) {
                    // Multiple choices - filter out invalid ones
                    $validIds = array_intersect(
                        $answerData['choice_ids'],
                        $validChoiceIds[$answer->question_id] ?? []
                    );

                    if (empty($validIds)) {
                        // No valid choices left, remove the answer
                        return [$answer->question_id => []];
                    }

                    // Update with only valid choice IDs
                    $answerData['choice_ids'] = array_values($validIds);
                }

                return [$answer->question_id => $answerData];
            })->filter();

            return Inertia::render('resident-exams/take', [
                'exam' => [
                    'id' => $assessment->id,
                    'type' => 'inservice',
                    'title' => $assessment->title,
                    'description' => $assessment->description,
                    'duration_minutes' => $assessment->duration_minutes,
                    'total_points' => $assessment->total_points,
                    'passing_score' => $assessment->passing_score,
                    'randomize_questions' => $assessment->randomize_questions,
                    'randomize_choices' => $assessment->randomize_choices,
                    'questions' => $questions->map(function ($q) use ($assessment, $attempt) {
                        $choices = $q->choices;

                        // Randomize choices if enabled (use attempt ID + question ID as seed)
                        if ($assessment->randomize_choices) {
                            $choices = $choices->shuffle($attempt->id + $q->id);
                        }

                        return [
                            'id' => $q->id,
                            'question_type' => $q->question_type,
                            'question_text' => $q->question_text,
                            'points' => $q->points,
                            'image_url' => $q->image_path ? Storage::disk('public')->url($q->image_path) : null,
                            'choices' => $choices->map(fn($c) => [
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
            ]);
        }

        abort(404);
    }

    /**
     * Save a single answer (auto-save as resident answers).
     */
    public function saveAnswer(Request $request, string $type, int $attempt): JsonResponse
    {
        $user = $request->user();

        // Parse answer_data if it's JSON string
        $answerData = $request->input('answer_data');
        if (is_string($answerData)) {
            $answerData = json_decode($answerData, true);
        }

        $validated = $request->validate([
            'question_id' => ['required', 'integer'],
        ]);

        $validated['answer_data'] = $answerData;

        if ($type === 'institution') {
            $attemptModel = $this->residentExamRepository->findInstitutionAttemptOrFail($attempt);

            // Verify this is the user's attempt
            if ($attemptModel->user_id !== $user->id) {
                abort(403);
            }

            $this->ensureAttemptSessionAccess($request, $attemptModel);

            // Verify attempt is still in progress
            if ($attemptModel->status !== 'in_progress') {
                return response()->json(['error' => 'This exam has already been submitted.'], 403);
            }

            // Check for existing answer to track changes
            $existingAnswer = $this->residentExamRepository->findInstitutionAnswer($attemptModel->id, $validated['question_id']);

            if ($existingAnswer) {
                // Update existing answer
                $changeCount = $existingAnswer->answer_change_count ?? 0;

                // Log suspicious behavior: more than 5 changes on same question
                if ($changeCount >= 5) {
                    Log::warning('Suspicious answer changes detected', [
                        'user_id' => $user->id,
                        'attempt_id' => $attemptModel->id,
                        'question_id' => $validated['question_id'],
                        'change_count' => $changeCount + 1,
                    ]);
                }

                $this->residentExamRepository->updateInstitutionAnswer($existingAnswer, [
                    'answer_data' => $validated['answer_data'],
                    'answer_change_count' => $changeCount + 1,
                ]);

                $answer = $existingAnswer;
            } else {
                // Create new answer
                $answer = $this->residentExamRepository->createInstitutionAnswer([
                    'attempt_id' => $attemptModel->id,
                    'question_id' => $validated['question_id'],
                    'answer_data' => $validated['answer_data'],
                    'answer_change_count' => 1,
                ]);
            }

            return response()->json(['success' => true]);
        } elseif ($type === 'inservice') {
            $attemptModel = $this->residentExamRepository->findNationalAttemptOrFail($attempt);

            // Verify this is the user's attempt
            if ($attemptModel->user_id !== $user->id) {
                abort(403);
            }

            $this->ensureAttemptSessionAccess($request, $attemptModel);

            // Verify attempt is still in progress
            if ($attemptModel->status !== 'in_progress') {
                return response()->json(['error' => 'This exam has already been submitted.'], 403);
            }

            // Check for existing answer to track changes
            $existingAnswer = $this->residentExamRepository->findNationalAnswer($attemptModel->id, $validated['question_id']);

            if ($existingAnswer) {
                // Update existing answer
                $changeCount = $existingAnswer->answer_change_count ?? 0;

                // Log suspicious behavior: more than 5 changes on same question
                if ($changeCount >= 5) {
                    Log::warning('Suspicious answer changes detected', [
                        'user_id' => $user->id,
                        'attempt_id' => $attemptModel->id,
                        'question_id' => $validated['question_id'],
                        'change_count' => $changeCount + 1,
                    ]);
                }

                $this->residentExamRepository->updateNationalAnswer($existingAnswer, [
                    'answer_data' => $validated['answer_data'],
                    'answer_change_count' => $changeCount + 1,
                ]);

                $answer = $existingAnswer;
            } else {
                // Create new answer
                $answer = $this->residentExamRepository->createNationalAnswer([
                    'attempt_id' => $attemptModel->id,
                    'question_id' => $validated['question_id'],
                    'answer_data' => $validated['answer_data'],
                    'answer_change_count' => 1,
                ]);
            }

            return response()->json(['success' => true]);
        }

        abort(404);
    }

    /**
     * Finalize exam submission (calculate score and mark as completed).
     */
    public function submit(Request $request, string $type, int $attempt): RedirectResponse
    {
        $user = $request->user();

        if ($type === 'institution') {
            $attemptModel = $this->residentExamRepository->findInstitutionAttemptOrFail($attempt);

            // Verify this is the user's attempt
            if ($attemptModel->user_id !== $user->id) {
                abort(403, 'You do not have access to this attempt.');
            }

            $this->ensureAttemptSessionAccess($request, $attemptModel);

            // Verify attempt is still in progress
            if ($attemptModel->status !== 'in_progress') {
                return back()->withErrors(['error' => 'This exam has already been submitted.']);
            }

            // Auto-grade all answers
            foreach ($attemptModel->answers as $answer) {
                $answer->autoGrade();
            }

            // Update question bank statistics for each answer
            $organizationId = $attemptModel->organization_id;
            foreach ($attemptModel->answers as $answer) {
                $question = $answer->question;

                if (! $question) {
                    continue;
                }

                // Try exact match first
                $bankQuestion = $this->residentExamRepository->findInstitutionQuestionBankMatch(
                    $question->question_text,
                    $organizationId,
                    $question->question_type,
                    $question->topic_id
                );

                if ($bankQuestion) {
                    // Calculate time spent (if available, otherwise use a default)
                    $timeSeconds = null; // Could be calculated from attempt timestamps if needed

                    // Update statistics for institution scope
                    $bankQuestion->updateStatistics(
                        $answer->is_correct ?? false,
                        $timeSeconds,
                        'institution',
                        $organizationId
                    );
                } else {
                    // Log when question is not found for debugging
                    Log::warning('Question bank entry not found for institution question', [
                        'question_id' => $question->id,
                        'question_text_preview' => mb_substr($question->question_text, 0, 100),
                        'question_type' => $question->question_type,
                        'organization_id' => $organizationId,
                    ]);
                }
            }

            // Calculate final score and mark as completed
            $attemptModel->calculateScore();
            $this->residentExamRepository->updateAttempt($attemptModel, [
                'submitted_at' => now(),
                'status' => 'completed',
                'active_session_id' => null,
            ]);

            return redirect('/resident-exams')->with('success', 'Exam submitted successfully! Score: ' . $attemptModel->percentage . '%');
        } elseif ($type === 'inservice') {
            $attemptModel = $this->residentExamRepository->findNationalAttemptOrFail($attempt);

            // Verify this is the user's attempt
            if ($attemptModel->user_id !== $user->id) {
                abort(403, 'You do not have access to this attempt.');
            }

            $this->ensureAttemptSessionAccess($request, $attemptModel);

            // Verify attempt is still in progress
            if ($attemptModel->status !== 'in_progress') {
                return back()->withErrors(['error' => 'This exam has already been submitted.']);
            }

            // Auto-grade all answers
            foreach ($attemptModel->answers as $answer) {
                $answer->autoGrade();
            }

            // Update question bank statistics for each answer
            foreach ($attemptModel->answers as $answer) {
                $question = $answer->question;

                if (! $question) {
                    continue;
                }

                // Try exact match first
                $bankQuestion = $this->residentExamRepository->findNationalQuestionBankMatch(
                    $question->question_text,
                    $question->question_type,
                    $question->topic
                );

                if ($bankQuestion) {
                    // Calculate time spent (if available, otherwise use a default)
                    $timeSeconds = null; // Could be calculated from attempt timestamps if needed

                    // Update statistics for national scope
                    $bankQuestion->updateStatistics(
                        $answer->is_correct ?? false,
                        $timeSeconds,
                        'national',
                        null
                    );
                } else {
                    // Log when question is not found for debugging
                    Log::warning('Question bank entry not found for national question', [
                        'question_id' => $question->id,
                        'question_text_preview' => mb_substr($question->question_text, 0, 100),
                        'question_type' => $question->question_type,
                        'topic' => $question->topic,
                    ]);
                }
            }

            // Calculate final score and mark as completed
            $attemptModel->calculateScore();
            $this->residentExamRepository->updateAttempt($attemptModel, [
                'submitted_at' => now(),
                'status' => 'completed',
                'active_session_id' => null,
            ]);

            return redirect('/resident-exams')->with('success', 'Exam submitted successfully! Score: ' . $attemptModel->percentage . '%');
        }

        abort(404);
    }

    /**
     * Display exam results for a specific exam.
     */
    public function results(Request $request, string $type, int $id): Response
    {
        $user = $request->user();

        if ($type === 'institution') {
            $assessment = $this->residentExamRepository->findInstitutionAssessmentOrFail($id);

            // Verify user has access
            if ($assessment->organization_id !== $user->current_organization_id) {
                abort(403, 'You do not have access to this exam.');
            }

            // Get the user's most recent completed attempt for THIS exam
            $attempt = $this->residentExamRepository->latestCompletedInstitutionAttempt($assessment, $user->id);

            if (! $attempt) {
                return redirect('/resident-exams')->with('error', 'No completed attempts found for this exam.');
            }

            // Get answers with their questions to show in the order they were answered
            $answers = $this->residentExamRepository->loadInstitutionResultAnswers($attempt);

            // Build results data
            $questionsData = $answers->map(function ($answer) {
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
                    'image_url' => $question->image_path ? Storage::disk('public')->url($question->image_path) : null,
                    'order' => $question->order,
                    'choices' => $question->choices->map(fn($choice) => [
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

            return Inertia::render('resident-exams/results', [
                'exam' => [
                    'id' => $assessment->id,
                    'title' => $assessment->title,
                    'description' => $assessment->description,
                    'type' => 'institution',
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
            ]);
        } elseif ($type === 'inservice') {
            $assessment = $this->residentExamRepository->findNationalAssessmentOrFail($id);

            // Get the user's best completed attempt
            $attempt = $this->residentExamRepository->bestCompletedNationalAttempt($assessment, $user->id);

            if (! $attempt) {
                return redirect('/resident-exams')->with('error', 'No completed attempts found for this exam.');
            }

            // Load questions with choices and answers
            $questions = $this->residentExamRepository->loadNationalQuestionsWithChoices($assessment);

            // Load user's answers for this attempt
            $answers = $this->residentExamRepository->loadNationalAnswersKeyedByQuestion($attempt);

            // Build results data
            $questionsData = $questions->map(function ($question) use ($answers) {
                $answer = $answers->get($question->id);
                $selectedChoiceIds = [];
                $isCorrect = false;

                if ($answer) {
                    if (isset($answer->answer_data['choice_id'])) {
                        $selectedChoiceIds = [$answer->answer_data['choice_id']];
                    } elseif (isset($answer->answer_data['choice_ids'])) {
                        $selectedChoiceIds = $answer->answer_data['choice_ids'];
                    }
                    $isCorrect = $answer->is_correct;
                }

                return [
                    'id' => $question->id,
                    'question_type' => $question->question_type,
                    'question_text' => $question->question_text,
                    'points' => $question->points,
                    'explanation' => $question->explanation,
                    'image_url' => $question->image_url,
                    'order' => $question->order,
                    'choices' => $question->choices->map(fn($choice) => [
                        'id' => $choice->id,
                        'choice_text' => $choice->choice_text,
                        'is_correct' => $choice->is_correct,
                        'order' => $choice->order,
                    ]),
                    'selected_choice_ids' => $selectedChoiceIds,
                    'is_correct' => $isCorrect,
                    'points_earned' => $isCorrect ? $question->points : 0,
                ];
            });

            return Inertia::render('resident-exams/results', [
                'exam' => [
                    'id' => $assessment->id,
                    'title' => $assessment->title,
                    'description' => $assessment->description,
                    'type' => 'inservice',
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
            ]);
        }

        abort(404);
    }

    /**
     * Get exam results as JSON for API calls.
     */
    public function resultsApi(Request $request, string $type, int $id): JsonResponse
    {
        $user = $request->user();

        if ($type === 'institution') {
            $assessment = $this->residentExamRepository->findInstitutionAssessmentOrFail($id);

            if ($assessment->organization_id !== $user->current_organization_id) {
                abort(403, 'You do not have access to this exam.');
            }

            // Get the user's most recent completed attempt for THIS exam
            $attempt = $this->residentExamRepository->latestCompletedInstitutionAttempt($assessment, $user->id);

            if (! $attempt) {
                return response()->json(['error' => 'No completed attempts found'], 404);
            }

            // Get answers with their questions to show in the order they were answered
            $answers = $this->residentExamRepository->loadInstitutionResultAnswers($attempt);

            $questionsData = $answers->map(function ($answer) {
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
                    'image_url' => $question->image_path ? Storage::disk('public')->url($question->image_path) : null,
                    'order' => $question->order,
                    'choices' => $question->choices->map(fn($choice) => [
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

            return response()->json([
                'exam' => [
                    'id' => $assessment->id,
                    'title' => $assessment->title,
                    'description' => $assessment->description,
                    'type' => 'institution',
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
            ]);
        } elseif ($type === 'inservice') {
            $assessment = $this->residentExamRepository->findNationalAssessmentOrFail($id);

            // Get the user's best completed attempt
            $attempt = $this->residentExamRepository->bestCompletedNationalAttempt($assessment, $user->id);

            if (! $attempt) {
                return response()->json(['error' => 'No completed attempts found'], 404);
            }

            // Load questions with choices and answers
            $questions = $this->residentExamRepository->loadNationalQuestionsWithChoices($assessment);

            // Load user's answers for this attempt
            $answers = $this->residentExamRepository->loadNationalAnswersKeyedByQuestion($attempt);

            $questionsData = $questions->map(function ($question) use ($answers) {
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
                    'image_url' => $question->image_path ? Storage::disk('public')->url($question->image_path) : null,
                    'order' => $question->order,
                    'choices' => $question->choices->map(fn($choice) => [
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

            return response()->json([
                'exam' => [
                    'id' => $assessment->id,
                    'title' => $assessment->title,
                    'description' => $assessment->description,
                    'type' => 'inservice',
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
            ]);
        }

        abort(404);
    }

    /**
     * Display exams available to the resident.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $currentOrganization = $user->currentOrganization;
        $organizationId = $user->current_organization_id;
        $isNational = $currentOrganization?->type === 'national';

        $availableExams = [];
        $upcomingExams = [];
        $completedExams = [];

        // Get Institution Exams only if current organization is NOT national
        $institutionExams = collect();
        if (! $isNational) {
            $institutionExams = $this->residentExamRepository->getPublishedInstitutionExamsForOrganization($organizationId);
        }

        foreach ($institutionExams as $exam) {
            // Check for in-progress attempt (must have started_at to be considered in progress)
            $inProgressAttempt = $this->residentExamRepository->hasStartedInProgressInstitutionAttempt($exam, $user->id);

            // Get user's completed/graded attempts for this exam
            $attempts = $this->residentExamRepository->getCompletedInstitutionAttempts($exam, $user->id);

            $attemptCount = $attempts->count();
            $bestScore = $attempts->max('score');
            $lastAttempt = $attempts->sortByDesc('submitted_at')->first();

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
                'max_attempts' => null, // Can be added later if needed
                'best_score' => $bestScore ? round(($bestScore / $exam->total_points) * 100, 2) : null,
                'last_attempted' => $lastAttempt?->submitted_at?->diffForHumans(),
                'has_in_progress_attempt' => $inProgressAttempt,
            ];

            if ($exam->isAvailable()) {
                $availableExams[] = $examData;
            } elseif ($exam->available_from && now()->isBefore($exam->available_from)) {
                $upcomingExams[] = $examData;
            } elseif ($attemptCount > 0) {
                $completedExams[] = $examData;
            }
        }

        // Get National In-Service Exams only if current organization IS national
        $nationalExams = collect();
        if ($isNational) {
            $nationalExams = $this->residentExamRepository->getPublishedNationalExams();
        }

        foreach ($nationalExams as $exam) {
            // Check for in-progress attempt (must have started_at to be considered in progress)
            $inProgressAttempt = $this->residentExamRepository->hasStartedInProgressNationalAttempt($exam, $user->id);

            // Get user's completed/graded attempts
            $attempts = $this->residentExamRepository->getCompletedNationalAttempts($exam, $user->id);

            $attemptCount = $attempts->count();
            $bestScore = $attempts->max('score');
            $lastAttempt = $attempts->sortByDesc('submitted_at')->first();

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
                'last_attempted' => $lastAttempt?->submitted_at?->diffForHumans(),
                'has_in_progress_attempt' => $inProgressAttempt,
            ];

            if ($exam->isAvailable()) {
                $availableExams[] = $examData;
            } elseif ($exam->scheduled_date && now()->isBefore($exam->scheduled_date)) {
                $upcomingExams[] = $examData;
            } elseif ($attemptCount > 0) {
                $completedExams[] = $examData;
            }
        }

        return Inertia::render('resident-exams/index', [
            'availableExams' => $availableExams,
            'completedExams' => $completedExams,
            'upcomingExams' => $upcomingExams,
        ]);
    }

    /**
     * Log browser or IP change during exam session.
     */
    public function logSessionChange(Request $request, string $type, int $attemptId)
    {
        $user = $request->user();

        $validated = $request->validate([
            'change_type' => ['required', 'in:ip_address,browser,both'],
            'previous_ip' => ['nullable', 'string'],
            'new_ip' => ['nullable', 'string'],
            'previous_user_agent' => ['nullable', 'string'],
            'new_user_agent' => ['nullable', 'string'],
            'browser_info' => ['nullable', 'array'],
        ]);

        // Verify attempt belongs to user
        if ($type === 'institution') {
            $attempt = $this->residentExamRepository->findInstitutionAttemptOrFail($attemptId);
        } else {
            $attempt = $this->residentExamRepository->findNationalAttemptOrFail($attemptId);
        }

        if ($attempt->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $this->ensureAttemptSessionAccess($request, $attempt);

        // Log the change
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

        // Increment counters
        if ($validated['change_type'] === 'ip_address' || $validated['change_type'] === 'both') {
            $this->residentExamRepository->incrementAttemptField($attempt, 'ip_changes_count');
        }
        if ($validated['change_type'] === 'browser' || $validated['change_type'] === 'both') {
            $this->residentExamRepository->incrementAttemptField($attempt, 'browser_changes_count');
        }

        return response()->noContent();
    }

    /**
     * Log activity and idle time for an exam attempt.
     */
    public function logActivity(Request $request, string $type, int $attemptId)
    {
        $user = $request->user();

        $validated = $request->validate([
            'idle_duration' => ['nullable', 'integer', 'min:0'], // seconds of idle time
        ]);

        // Verify attempt belongs to user
        if ($type === 'institution') {
            $attempt = $this->residentExamRepository->findInstitutionAttemptOrFail($attemptId);
        } else {
            $attempt = $this->residentExamRepository->findNationalAttemptOrFail($attemptId);
        }

        if ($attempt->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $this->ensureAttemptSessionAccess($request, $attempt);

        // Update last activity
        $this->residentExamRepository->updateAttempt($attempt, ['last_activity_at' => now()]);

        // If idle duration provided, log it
        if (isset($validated['idle_duration']) && $validated['idle_duration'] > 0) {
            $idleDuration = $validated['idle_duration'];

            // Calculate when idle period started
            $endedAt = now();
            $startedAt = $endedAt->copy()->subSeconds($idleDuration);

            // Store individual idle period with timestamps
            $this->residentExamRepository->createIdlePeriod([
                'attempt_type' => $type,
                'attempt_id' => $attemptId,
                'user_id' => $user->id,
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
                'duration_seconds' => $idleDuration,
            ]);

            // Update aggregate counters
            $this->residentExamRepository->incrementAttemptField($attempt, 'total_idle_time', $idleDuration);
            $this->residentExamRepository->incrementAttemptField($attempt, 'idle_periods_count');

            // Update max idle duration if this is longer
            if ($idleDuration > $attempt->max_idle_duration) {
                $this->residentExamRepository->updateAttempt($attempt, ['max_idle_duration' => $idleDuration]);
            }
        }

        return response()->noContent();
    }

    /**
     * Get current IP address for change detection.
     */
    public function getCurrentIp(Request $request, string $type, int $attemptId)
    {
        $user = $request->user();

        // Get attempt
        if ($type === 'institution') {
            $attempt = $this->residentExamRepository->findInstitutionAttemptOrFail($attemptId);
        } else {
            $attempt = $this->residentExamRepository->findNationalAttemptOrFail($attemptId);
        }

        // Verify ownership
        if ($attempt->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $this->ensureAttemptSessionAccess($request, $attempt);

        return response()->json([
            'ip_address' => $request->ip(),
        ]);
    }

    /**
     * Get session info for browser change detection.
     */
    public function getSessionInfo(Request $request, string $type, int $attemptId)
    {
        $user = $request->user();

        // Get attempt
        if ($type === 'institution') {
            $attempt = $this->residentExamRepository->findInstitutionAttemptOrFail($attemptId);
        } else {
            $attempt = $this->residentExamRepository->findNationalAttemptOrFail($attemptId);
        }

        // Verify ownership
        if ($attempt->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $this->ensureAttemptSessionAccess($request, $attempt);

        return response()->json([
            'user_agent' => $attempt->user_agent,
            'ip_address' => $attempt->ip_address,
            'browser_metadata' => $attempt->browser_metadata,
        ]);
    }

    /**
     * Update exam attempt metadata (called from frontend after page load).
     */
    public function updateMetadata(Request $request, string $type, int $attemptId)
    {
        $user = $request->user();

        $validated = $request->validate([
            'browser_metadata' => ['required', 'array'],
            'connection_type' => ['nullable', 'string'],
            'connection_speed' => ['nullable', 'numeric'],
        ]);

        // Verify attempt belongs to user
        if ($type === 'institution') {
            $attempt = $this->residentExamRepository->findInstitutionAttemptOrFail($attemptId);
        } else {
            $attempt = $this->residentExamRepository->findNationalAttemptOrFail($attemptId);
        }

        if ($attempt->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $this->ensureAttemptSessionAccess($request, $attempt);

        // Always capture IP and user agent at exam start
        // Update browser metadata, connection info if not already set
        $updateData = [
            'last_activity_at' => now(),
        ];

        // Always update IP address (captures initial IP at exam start)
        if (! $attempt->ip_address) {
            $updateData['ip_address'] = $request->ip();
        }

        // Always update user agent if not set
        if (! $attempt->user_agent) {
            $updateData['user_agent'] = $request->userAgent();
        }

        // Update browser metadata if not already set
        if (! $attempt->browser_metadata) {
            $updateData['browser_metadata'] = $validated['browser_metadata'];
            $updateData['connection_type'] = $validated['connection_type'] ?? null;
            $updateData['connection_speed'] = $validated['connection_speed'] ?? null;
        }

        $this->residentExamRepository->updateAttempt($attempt, $updateData);

        return response()->json(['success' => true]);
    }

    private function claimOrAbortAttemptSession(Request $request, InstitutionAttempt|NationalAttempt $attempt): void
    {
        $currentSessionId = $request->session()->getId();

        if (! $attempt->active_session_id) {
            $this->residentExamRepository->updateAttempt($attempt, [
                'active_session_id' => $currentSessionId,
            ]);
            $attempt->active_session_id = $currentSessionId;

            return;
        }

        if ($attempt->active_session_id !== $currentSessionId) {
            abort(409, self::EXAM_SESSION_CONFLICT_MESSAGE);
        }
    }

    private function ensureAttemptSessionAccess(Request $request, InstitutionAttempt|NationalAttempt $attempt): void
    {
        $currentSessionId = $request->session()->getId();

        if (! $attempt->active_session_id) {
            $this->residentExamRepository->updateAttempt($attempt, [
                'active_session_id' => $currentSessionId,
            ]);
            $attempt->active_session_id = $currentSessionId;

            return;
        }

        if ($attempt->active_session_id !== $currentSessionId) {
            if ($request->expectsJson() || $request->ajax()) {
                response()->json([
                    'error' => self::EXAM_SESSION_CONFLICT_MESSAGE,
                ], 409)->throwResponse();
            }

            abort(409, self::EXAM_SESSION_CONFLICT_MESSAGE);
        }
    }
}
