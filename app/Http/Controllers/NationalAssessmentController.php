<?php

namespace App\Http\Controllers;

use App\Models\National\NationalAssessment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NationalAssessmentController extends Controller
{
    /**
     * Display a listing of national in-service exams.
     */
    public function index(Request $request): Response
    {
        $assessments = NationalAssessment::query()
            ->with(['creator:id,name'])
            ->withCount('questions')
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->input('year'), function ($query, $year) {
                $query->where('exam_year', $year);
            })
            ->when($request->input('status'), function ($query, $status) {
                if ($status === 'published') {
                    $query->where('is_published', true);
                } elseif ($status === 'draft') {
                    $query->where('is_published', false);
                }
            })
            ->orderBy($request->input('sort', 'exam_year'), $request->input('direction', 'desc'))
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($assessment) => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'description' => $assessment->description,
                'exam_year' => $assessment->exam_year,
                'exam_period' => $assessment->exam_period,
                'questions_count' => $assessment->questions_count,
                'total_points' => $assessment->total_points,
                'passing_score' => $assessment->passing_score,
                'duration_minutes' => $assessment->duration_minutes,
                'is_published' => $assessment->is_published,
                'is_available' => $assessment->isAvailable(),
                'scheduled_date' => $assessment->scheduled_date?->format('Y-m-d H:i'),
                'results_release_date' => $assessment->results_release_date?->format('Y-m-d H:i'),
                'can_view_results' => $assessment->canViewResults(),
                'national_ranking_enabled' => $assessment->national_ranking_enabled,
                'created_by' => $assessment->creator->name,
                'created_at' => $assessment->created_at->format('Y-m-d'),
                'updated_at' => $assessment->updated_at->diffForHumans(),
            ]);

        // Get unique years for filter
        $years = NationalAssessment::distinct()->pluck('exam_year')->sort()->values();

        return Inertia::render('in-service/index', [
            'assessments' => $assessments,
            'filters' => $request->only(['search', 'year', 'status']),
            'years' => $years,
        ]);
    }

    /**
     * Store a newly created national assessment.
     * Only BOP and System Admin can create.
     */
    public function store(Request $request): RedirectResponse
    {
        // Additional role check (route middleware should also protect this)
        if (! $request->user()->hasAnyRole(['System Admin', 'BOP'])) {
            abort(403, 'Only System Admin and BOP can create national assessments.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'exam_year' => ['required', 'integer', 'min:2024'],
            'exam_period' => ['required', 'string', 'max:50'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'passing_score' => ['required', 'integer', 'min:0'],
            'randomize_questions' => ['boolean'],
            'randomize_choices' => ['boolean'],
            'show_results_immediately' => ['boolean'],
            'allow_review' => ['boolean'],
            'national_ranking_enabled' => ['boolean'],
            'institution_comparison_enabled' => ['boolean'],
            'scheduled_date' => ['nullable', 'date'],
            'results_release_date' => ['nullable', 'date', 'after:scheduled_date'],
            'questions' => ['nullable', 'array'],
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['total_points'] = 0;

        $assessment = NationalAssessment::create($validated);

        // If questions were provided, save them
        if (! empty($validated['questions'])) {
            $totalPoints = 0;

            foreach ($validated['questions'] as $q) {
                $question = $assessment->questions()->create([
                    'question_type' => $q['question_type'],
                    'question_text' => $q['question_text'],
                    'points' => $q['points'],
                    'difficulty_level' => $q['difficulty_level'] ?? null,
                    'topic' => $q['topic'] ?? null,
                    'order' => $q['order'] ?? 0,
                ]);

                // Handle choices for MCQ and Multiple Select
                if (in_array($q['question_type'], ['multiple_choice', 'multiple_select'])) {
                    foreach ($q['choices'] ?? [] as $idx => $c) {
                        $question->choices()->create([
                            'choice_text' => $c['choice_text'],
                            'is_correct' => (bool) ($c['is_correct'] ?? false),
                            'order' => $idx,
                        ]);
                    }
                }

                // True/False stored as two choices
                if ($q['question_type'] === 'true_false') {
                    $answer = filter_var($q['answer'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $question->choices()->createMany([
                        ['choice_text' => 'True', 'is_correct' => $answer === true, 'order' => 0],
                        ['choice_text' => 'False', 'is_correct' => $answer === false, 'order' => 1],
                    ]);
                }

                $totalPoints += $q['points'];
            }

            $assessment->update(['total_points' => $totalPoints]);
        }

        return back()->with('success', 'National assessment created successfully');
    }

    /**
     * Display the specified national assessment.
     */
    public function show(NationalAssessment $assessment): Response
    {
        $assessment->load(['questions.choices', 'creator:id,name']);

        return Inertia::render('in-service/show', [
            'assessment' => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'description' => $assessment->description,
                'exam_year' => $assessment->exam_year,
                'exam_period' => $assessment->exam_period,
                'duration_minutes' => $assessment->duration_minutes,
                'total_points' => $assessment->total_points,
                'passing_score' => $assessment->passing_score,
                'is_published' => $assessment->is_published,
                'scheduled_date' => $assessment->scheduled_date?->format('Y-m-d H:i'),
                'results_release_date' => $assessment->results_release_date?->format('Y-m-d H:i'),
                'national_ranking_enabled' => $assessment->national_ranking_enabled,
                'institution_comparison_enabled' => $assessment->institution_comparison_enabled,
                'questions' => $assessment->questions->map(fn ($q) => [
                    'id' => $q->id,
                    'question_type' => $q->question_type,
                    'question_text' => $q->question_text,
                    'points' => $q->points,
                    'explanation' => $q->explanation,
                    'difficulty_level' => $q->difficulty_level,
                    'topic' => $q->topic,
                    'order' => $q->order,
                    'choices' => $q->choices->map(fn ($c) => [
                        'id' => $c->id,
                        'choice_text' => $c->choice_text,
                        'is_correct' => $c->is_correct,
                        'order' => $c->order,
                    ]),
                ]),
                'created_by' => $assessment->creator->name,
                'created_at' => $assessment->created_at->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Update the specified national assessment.
     */
    public function update(Request $request, NationalAssessment $assessment): RedirectResponse
    {
        // Additional role check
        if (! $request->user()->hasAnyRole(['System Admin', 'BOP'])) {
            abort(403, 'Only System Admin and BOP can edit national assessments.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'exam_year' => ['required', 'integer', 'min:2024'],
            'exam_period' => ['required', 'string', 'max:50'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'passing_score' => ['required', 'integer', 'min:0'],
            'randomize_questions' => ['boolean'],
            'randomize_choices' => ['boolean'],
            'show_results_immediately' => ['boolean'],
            'allow_review' => ['boolean'],
            'is_published' => ['boolean'],
            'national_ranking_enabled' => ['boolean'],
            'institution_comparison_enabled' => ['boolean'],
            'scheduled_date' => ['nullable', 'date'],
            'results_release_date' => ['nullable', 'date', 'after:scheduled_date'],
        ]);

        $assessment->update($validated);

        return back()->with('success', 'National assessment updated successfully');
    }

    /**
     * Remove the specified national assessment.
     */
    public function destroy(NationalAssessment $assessment): RedirectResponse
    {
        // Additional role check
        if (! auth()->user()->hasAnyRole(['System Admin', 'BOP'])) {
            abort(403, 'Only System Admin and BOP can delete national assessments.');
        }

        // Check if there are any attempts
        if ($assessment->attempts()->count() > 0) {
            return back()->withErrors([
                'error' => 'Cannot delete national assessment that has been attempted by residents.',
            ]);
        }

        $assessment->delete();

        return back()->with('success', 'National assessment deleted successfully');
    }
}
