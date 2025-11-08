<?php

namespace App\Http\Controllers;

use App\Models\Institution\InstitutionAssessment;
use App\Models\National\NationalAssessment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResidentExamController extends Controller
{
    /**
     * Show the exam taking page (start or resume).
     */
    public function take(Request $request, string $type, int $id): Response
    {
        $user = $request->user();

        if ($type === 'institution') {
            $assessment = InstitutionAssessment::findOrFail($id);

            // Verify user has access (same organization)
            if ($assessment->organization_id !== $user->current_organization_id) {
                abort(403, 'You do not have access to this exam.');
            }

            // Check if exam is available
            if (! $assessment->isAvailable()) {
                abort(403, 'This exam is not currently available.');
            }

            // Check for existing in-progress attempt
            $attempt = $assessment->attempts()
                ->where('user_id', $user->id)
                ->where('status', 'in_progress')
                ->first();

            // If no in-progress attempt, create a new one
            if (! $attempt) {
                $attempt = $assessment->attempts()->create([
                    'user_id' => $user->id,
                    'year_level' => $user->resident?->year_level,
                    'organization_id' => $user->current_organization_id,
                    'started_at' => now(),
                    'total_points' => $assessment->total_points,
                    'status' => 'in_progress',
                ]);
            }

            // Load questions with choices in proper order
            $questions = $assessment->questions()
                ->with(['choices' => fn ($query) => $query->orderBy('order')])
                ->orderBy('order')
                ->get();

            // Apply randomization if enabled (use attempt ID as seed for consistency)
            if ($assessment->randomize_questions) {
                $questions = $questions->shuffle($attempt->id);
            }

            // Load existing answers
            $attempt->load('answers');
            $savedAnswers = $attempt->answers->mapWithKeys(fn ($answer) => [
                $answer->question_id => $answer->answer_data,
            ]);

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
                            'image_url' => $q->image_path ? \Storage::disk('public')->url($q->image_path) : null,
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
            ]);
        } elseif ($type === 'inservice') {
            $assessment = NationalAssessment::findOrFail($id);

            // Check if exam is available
            if (! $assessment->isAvailable()) {
                abort(403, 'This exam is not currently available.');
            }

            // Check for existing in-progress attempt
            $attempt = $assessment->attempts()
                ->where('user_id', $user->id)
                ->where('status', 'in_progress')
                ->first();

            // If no in-progress attempt, create a new one
            if (! $attempt) {
                $attempt = $assessment->attempts()->create([
                    'user_id' => $user->id,
                    'year_level' => $user->resident?->year_level,
                    'organization_id' => $user->current_organization_id,
                    'started_at' => now(),
                    'total_points' => $assessment->total_points,
                    'status' => 'in_progress',
                ]);
            }

            // Load questions with choices in proper order
            $questions = $assessment->questions()
                ->with(['choices' => fn ($query) => $query->orderBy('order')])
                ->orderBy('order')
                ->get();

            // Apply randomization if enabled (use attempt ID as seed for consistency)
            if ($assessment->randomize_questions) {
                $questions = $questions->shuffle($attempt->id);
            }

            // Load existing answers
            $attempt->load('answers');
            $savedAnswers = $attempt->answers->mapWithKeys(fn ($answer) => [
                $answer->question_id => $answer->answer_data,
            ]);

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
                            'image_url' => $q->image_path ? \Storage::disk('public')->url($q->image_path) : null,
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
            ]);
        }

        abort(404);
    }

    /**
     * Save a single answer (auto-save as resident answers).
     */
    public function saveAnswer(Request $request, string $type, int $attempt): \Illuminate\Http\JsonResponse
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
            $attemptModel = \App\Models\Institution\InstitutionAttempt::findOrFail($attempt);

            // Verify this is the user's attempt
            if ($attemptModel->user_id !== $user->id) {
                abort(403);
            }

            // Verify attempt is still in progress
            if ($attemptModel->status !== 'in_progress') {
                return response()->json(['error' => 'This exam has already been submitted.'], 403);
            }

            // Check for existing answer to track changes
            $existingAnswer = \App\Models\Institution\InstitutionAnswer::where('attempt_id', $attemptModel->id)
                ->where('question_id', $validated['question_id'])
                ->first();

            if ($existingAnswer) {
                // Update existing answer
                $changeCount = $existingAnswer->answer_change_count ?? 0;

                // Log suspicious behavior: more than 5 changes on same question
                if ($changeCount >= 5) {
                    \Log::warning('Suspicious answer changes detected', [
                        'user_id' => $user->id,
                        'attempt_id' => $attemptModel->id,
                        'question_id' => $validated['question_id'],
                        'change_count' => $changeCount + 1,
                    ]);
                }

                $existingAnswer->update([
                    'answer_data' => $validated['answer_data'],
                    'answer_change_count' => $changeCount + 1,
                ]);

                $answer = $existingAnswer;
            } else {
                // Create new answer
                $answer = \App\Models\Institution\InstitutionAnswer::create([
                    'attempt_id' => $attemptModel->id,
                    'question_id' => $validated['question_id'],
                    'answer_data' => $validated['answer_data'],
                    'answer_change_count' => 1,
                ]);
            }

            return response()->json(['success' => true]);
        } elseif ($type === 'inservice') {
            $attemptModel = \App\Models\National\NationalAttempt::findOrFail($attempt);

            // Verify this is the user's attempt
            if ($attemptModel->user_id !== $user->id) {
                abort(403);
            }

            // Verify attempt is still in progress
            if ($attemptModel->status !== 'in_progress') {
                return response()->json(['error' => 'This exam has already been submitted.'], 403);
            }

            // Check for existing answer to track changes
            $existingAnswer = \App\Models\National\NationalAnswer::where('attempt_id', $attemptModel->id)
                ->where('question_id', $validated['question_id'])
                ->first();

            if ($existingAnswer) {
                // Update existing answer
                $changeCount = $existingAnswer->answer_change_count ?? 0;

                // Log suspicious behavior: more than 5 changes on same question
                if ($changeCount >= 5) {
                    \Log::warning('Suspicious answer changes detected', [
                        'user_id' => $user->id,
                        'attempt_id' => $attemptModel->id,
                        'question_id' => $validated['question_id'],
                        'change_count' => $changeCount + 1,
                    ]);
                }

                $existingAnswer->update([
                    'answer_data' => $validated['answer_data'],
                    'answer_change_count' => $changeCount + 1,
                ]);

                $answer = $existingAnswer;
            } else {
                // Create new answer
                $answer = \App\Models\National\NationalAnswer::create([
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
    public function submit(Request $request, string $type, int $attempt): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        if ($type === 'institution') {
            $attemptModel = \App\Models\Institution\InstitutionAttempt::findOrFail($attempt);

            // Verify this is the user's attempt
            if ($attemptModel->user_id !== $user->id) {
                abort(403, 'You do not have access to this attempt.');
            }

            // Verify attempt is still in progress
            if ($attemptModel->status !== 'in_progress') {
                return back()->withErrors(['error' => 'This exam has already been submitted.']);
            }

            // Auto-grade all answers
            foreach ($attemptModel->answers as $answer) {
                $answer->autoGrade();
            }

            // Calculate final score and mark as completed
            $attemptModel->calculateScore();
            $attemptModel->update([
                'submitted_at' => now(),
                'status' => 'completed',
            ]);

            return redirect('/resident-exams')->with('success', 'Exam submitted successfully! Score: '.$attemptModel->percentage.'%');
        } elseif ($type === 'inservice') {
            $attemptModel = \App\Models\National\NationalAttempt::findOrFail($attempt);

            // Verify this is the user's attempt
            if ($attemptModel->user_id !== $user->id) {
                abort(403, 'You do not have access to this attempt.');
            }

            // Verify attempt is still in progress
            if ($attemptModel->status !== 'in_progress') {
                return back()->withErrors(['error' => 'This exam has already been submitted.']);
            }

            // Auto-grade all answers
            foreach ($attemptModel->answers as $answer) {
                $answer->autoGrade();
            }

            // Calculate final score and mark as completed
            $attemptModel->calculateScore();
            $attemptModel->update([
                'submitted_at' => now(),
                'status' => 'completed',
            ]);

            return redirect('/resident-exams')->with('success', 'Exam submitted successfully! Score: '.$attemptModel->percentage.'%');
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
            $assessment = InstitutionAssessment::findOrFail($id);

            // Verify user has access
            if ($assessment->organization_id !== $user->current_organization_id) {
                abort(403, 'You do not have access to this exam.');
            }

            // Get the user's most recent completed attempt for THIS exam
            $attempt = $assessment->attempts()
                ->where('user_id', $user->id)
                ->whereIn('status', ['completed', 'graded'])
                ->orderBy('submitted_at', 'desc')
                ->first();

            if (! $attempt) {
                return redirect('/resident-exams')->with('error', 'No completed attempts found for this exam.');
            }

            // Get answers with their questions to show in the order they were answered
            $answers = $attempt->answers()
                ->with(['question.choices' => fn ($query) => $query->orderBy('order')])
                ->orderBy('id')
                ->get();

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
                    'image_url' => $question->image_url,
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
            $assessment = NationalAssessment::findOrFail($id);

            // Get the user's best completed attempt
            $attempt = $assessment->attempts()
                ->where('user_id', $user->id)
                ->whereIn('status', ['completed', 'graded'])
                ->orderBy('score', 'desc')
                ->orderBy('submitted_at', 'desc')
                ->first();

            if (! $attempt) {
                return redirect('/resident-exams')->with('error', 'No completed attempts found for this exam.');
            }

            // Load questions with choices and answers
            $questions = $assessment->questions()
                ->with(['choices' => fn ($query) => $query->orderBy('order')])
                ->orderBy('order')
                ->get();

            // Load user's answers for this attempt
            $answers = $attempt->answers()
                ->with('question.choices')
                ->get()
                ->keyBy('question_id');

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
                    'choices' => $question->choices->map(fn ($choice) => [
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
    public function resultsApi(Request $request, string $type, int $id): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        if ($type === 'institution') {
            $assessment = InstitutionAssessment::findOrFail($id);

            if ($assessment->organization_id !== $user->current_organization_id) {
                abort(403, 'You do not have access to this exam.');
            }

            // Get the user's most recent completed attempt for THIS exam
            $attempt = $assessment->attempts()
                ->where('user_id', $user->id)
                ->whereIn('status', ['completed', 'graded'])
                ->orderBy('submitted_at', 'desc')
                ->first();

            if (! $attempt) {
                return response()->json(['error' => 'No completed attempts found'], 404);
            }

            // Get answers with their questions to show in the order they were answered
            $answers = $attempt->answers()
                ->with(['question.choices' => fn ($query) => $query->orderBy('order')])
                ->orderBy('id')
                ->get();

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
                    'image_url' => $question->image_url,
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
        }

        abort(404);
    }

    /**
     * Display exams available to the resident.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $organizationId = $user->current_organization_id;

        $availableExams = [];
        $upcomingExams = [];
        $completedExams = [];

        // Get Institution Exams from the resident's organization
        $institutionExams = InstitutionAssessment::query()
            ->where('organization_id', $organizationId)
            ->where('is_published', true)
            ->with(['questions'])
            ->withCount('questions')
            ->get();

        foreach ($institutionExams as $exam) {
            // Check for in-progress attempt
            $inProgressAttempt = $exam->attempts()
                ->where('user_id', $user->id)
                ->where('status', 'in_progress')
                ->exists();

            // Get user's completed/graded attempts for this exam
            $attempts = $exam->attempts()
                ->where('user_id', $user->id)
                ->whereIn('status', ['completed', 'graded'])
                ->get();

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

        // Get National In-Service Exams (if applicable)
        $nationalExams = NationalAssessment::query()
            ->where('is_published', true)
            ->with(['questions'])
            ->withCount('questions')
            ->get();

        foreach ($nationalExams as $exam) {
            // Check for in-progress attempt
            $inProgressAttempt = $exam->attempts()
                ->where('user_id', $user->id)
                ->where('status', 'in_progress')
                ->exists();

            // Get user's completed/graded attempts
            $attempts = $exam->attempts()
                ->where('user_id', $user->id)
                ->whereIn('status', ['completed', 'graded'])
                ->get();

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
}
