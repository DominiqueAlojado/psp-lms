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

            // Load questions with choices
            $assessment->load(['questions.choices']);

            // Check for existing in-progress attempt
            $attempt = $assessment->attempts()
                ->where('user_id', $user->id)
                ->where('status', 'in_progress')
                ->first();

            // If no in-progress attempt, create a new one
            if (! $attempt) {
                $attempt = $assessment->attempts()->create([
                    'user_id' => $user->id,
                    'organization_id' => $user->current_organization_id,
                    'started_at' => now(),
                    'total_points' => $assessment->total_points,
                    'status' => 'in_progress',
                ]);
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
                    'questions' => $assessment->questions->map(fn ($q) => [
                        'id' => $q->id,
                        'question_type' => $q->question_type,
                        'question_text' => $q->question_text,
                        'points' => $q->points,
                        'image_url' => $q->image_path ? \Storage::disk('public')->url($q->image_path) : null,
                        'choices' => $q->choices->map(fn ($c) => [
                            'id' => $c->id,
                            'choice_text' => $c->choice_text,
                        ]),
                    ]),
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

            // Load questions with choices
            $assessment->load(['questions.choices']);

            // Check for existing in-progress attempt
            $attempt = $assessment->attempts()
                ->where('user_id', $user->id)
                ->where('status', 'in_progress')
                ->first();

            // If no in-progress attempt, create a new one
            if (! $attempt) {
                $attempt = $assessment->attempts()->create([
                    'user_id' => $user->id,
                    'organization_id' => $user->current_organization_id,
                    'started_at' => now(),
                    'total_points' => $assessment->total_points,
                    'status' => 'in_progress',
                ]);
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
                    'questions' => $assessment->questions->map(fn ($q) => [
                        'id' => $q->id,
                        'question_type' => $q->question_type,
                        'question_text' => $q->question_text,
                        'points' => $q->points,
                        'image_url' => $q->image_path ? \Storage::disk('public')->url($q->image_path) : null,
                        'choices' => $q->choices->map(fn ($c) => [
                            'id' => $c->id,
                            'choice_text' => $c->choice_text,
                        ]),
                    ]),
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

            // Update or create answer
            $answer = \App\Models\Institution\InstitutionAnswer::updateOrCreate(
                [
                    'attempt_id' => $attemptModel->id,
                    'question_id' => $validated['question_id'],
                ],
                [
                    'answer_data' => $validated['answer_data'],
                ]
            );

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

            // Update or create answer
            $answer = \App\Models\National\NationalAnswer::updateOrCreate(
                [
                    'attempt_id' => $attemptModel->id,
                    'question_id' => $validated['question_id'],
                ],
                [
                    'answer_data' => $validated['answer_data'],
                ]
            );

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
            $attemptModel->update(['submitted_at' => now()]);

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
            $attemptModel->update(['submitted_at' => now()]);

            return redirect('/resident-exams')->with('success', 'Exam submitted successfully! Score: '.$attemptModel->percentage.'%');
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
            // Get user's attempts for this exam
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
