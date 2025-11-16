<?php

namespace App\Http\Controllers;

use App\Models\National\NationalAssessment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use App\Models\National\NationalQuestion;
use App\Models\National\NationalQuestionChoice;
use Illuminate\Support\Facades\Storage;

class NationalAssessmentController extends Controller
{
    /**
     * Display a listing of national in-service exams.
     */
    public function index(Request $request): Response
    {
        $query = NationalAssessment::query()
            ->with(['questions', 'creator:id,name'])
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
            ->orderBy($request->input('sort', 'created_at'), $request->input('direction', 'desc'));

        $assessments = $query
            ->paginate(15)
            ->withQueryString()
            ->through(function (NationalAssessment $assessment) {
                return [
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
                    'created_by' => $assessment->creator?->name,
                    'created_at' => $assessment->created_at?->format('Y-m-d'),
                    'updated_at' => $assessment->updated_at?->diffForHumans(),
                ];
            });

        $years = NationalAssessment::query()
            ->select('exam_year')
            ->distinct()
            ->orderByDesc('exam_year')
            ->pluck('exam_year')
            ->toArray();

        return Inertia::render('inservice-exams/index', [
            'assessments' => $assessments,
            'filters' => $request->only(['search', 'year', 'status']),
            'years' => $years,
        ]);
    }

    /**
     * Store a newly created national in-service exam.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'exam_year' => ['required', 'integer', 'min:2000', 'max:3000'],
            'exam_period' => ['required', 'string', 'max:100'],
            'category' => ['required', 'in:anatomic-pathology-theoretical,anatomic-pathology-projection,clinical-pathology-theoretical,clinical-pathology-projection'],
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
            'results_release_date' => ['nullable', 'date'],
        ]);

        $assessment = NationalAssessment::create([
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
            'created_by' => $request->user()->id,
        ]);

        return back()->with('assessment_id', $assessment->id);
    }

    /**
     * Store questions for a national in-service exam.
     */
    public function storeQuestions(Request $request, NationalAssessment $assessment): RedirectResponse
    {
        $validated = $request->validate([
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question_type' => ['required', 'in:multiple_choice,multiple_select,true_false'],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.points' => ['required', 'integer', 'min:1'],
            'questions.*.difficulty_level' => ['nullable', 'in:easy,medium,hard'],
            'questions.*.topic' => ['nullable', 'string', 'max:255'],
            'questions.*.choices' => ['nullable', 'array'],
            'questions.*.choices.*.choice_text' => ['required_with:questions.*.choices', 'string'],
            'questions.*.choices.*.is_correct' => ['required_with:questions.*.choices', 'boolean'],
            'questions.*.answer' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($validated, $assessment) {
            $order = ($assessment->questions()->max('order') ?? 0) + 1;
            $totalPointsAdded = 0;

            foreach ($validated['questions'] as $q) {
                $question = NationalQuestion::create([
                    'assessment_id' => $assessment->id,
                    'question_type' => $q['question_type'],
                    'question_text' => $q['question_text'],
                    'points' => $q['points'],
                    'difficulty_level' => $q['difficulty_level'] ?? null,
                    'topic' => $q['topic'] ?? null,
                    'order' => $order++,
                ]);

                $totalPointsAdded += (int) $q['points'];

                // Handle choices for types that require them
                if (in_array($q['question_type'], ['multiple_choice', 'multiple_select'])) {
                    $choiceOrder = 1;
                    foreach ($q['choices'] ?? [] as $choice) {
                        NationalQuestionChoice::create([
                            'question_id' => $question->id,
                            'choice_text' => $choice['choice_text'],
                            'is_correct' => (bool) ($choice['is_correct'] ?? false),
                            'order' => $choiceOrder++,
                        ]);
                    }
                } elseif ($q['question_type'] === 'true_false') {
                    // Normalize true/false to two choices
                    NationalQuestionChoice::create([
                        'question_id' => $question->id,
                        'choice_text' => 'True',
                        'is_correct' => (bool) ($q['answer'] ?? true) === true,
                        'order' => 1,
                    ]);
                    NationalQuestionChoice::create([
                        'question_id' => $question->id,
                        'choice_text' => 'False',
                        'is_correct' => (bool) ($q['answer'] ?? true) === false,
                        'order' => 2,
                    ]);
                }
            }

            // Update total points
            $assessment->increment('total_points', $totalPointsAdded);
        });

        return back()->with('success', 'Questions saved successfully.');
    }

    /**
     * Edit page for a national assessment.
     */
    public function edit(NationalAssessment $assessment): Response
    {
        $assessment->load(['questions.choices', 'creator:id,name']);

        return Inertia::render('inservice-exams/edit', [
            'assessment' => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'description' => $assessment->description,
                'category' => $assessment->category,
                'duration_minutes' => $assessment->duration_minutes,
                'total_points' => $assessment->total_points,
                'passing_score' => $assessment->passing_score,
                'randomize_questions' => $assessment->randomize_questions,
                'randomize_choices' => $assessment->randomize_choices,
                'show_results_immediately' => $assessment->show_results_immediately,
                'allow_review' => $assessment->allow_review,
                'is_published' => $assessment->is_published,
                'available_from' => $assessment->scheduled_date?->format('Y-m-d\TH:i'),
                'available_until' => $assessment->results_release_date?->format('Y-m-d\TH:i'),
                'questions' => $assessment->questions->map(fn($q) => [
                    'id' => $q->id,
                    'question_type' => $q->question_type,
                    'question_text' => $q->question_text,
                    'points' => $q->points,
                    'topic' => $q->topic,
                    'order' => $q->order,
                    'image_path' => $q->image_path,
                    'image_url' => $q->image_path ? Storage::url($q->image_path) : null,
                    'choices' => $q->choices->map(fn($c) => [
                        'id' => $c->id,
                        'choice_text' => $c->choice_text,
                        'is_correct' => $c->is_correct,
                        'order' => $c->order,
                    ]),
                ]),
                'created_by' => $assessment->creator?->name,
                'created_at' => $assessment->created_at?->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Save or update a single question.
     */
    public function saveOneQuestion(Request $request, NationalAssessment $assessment): RedirectResponse
    {
        $validated = $request->validate([
            'id' => ['nullable', 'integer', 'exists:national_questions,id'],
            'question_type' => ['required', 'in:multiple_choice,multiple_select,true_false'],
            'question_text' => ['required', 'string'],
            'points' => ['required', 'integer', 'min:1'],
            'topic' => ['nullable', 'string', 'max:255'],
            'choices' => ['nullable', 'array'],
            'choices.*.id' => ['nullable', 'integer', 'exists:national_question_choices,id'],
            'choices.*.choice_text' => ['required_with:choices', 'string'],
            'choices.*.is_correct' => ['required_with:choices', 'boolean'],
            'answer' => ['nullable', 'boolean'],
            'image' => ['nullable', 'string'], // base64
        ]);

        $question = NationalQuestion::query()
            ->where('assessment_id', $assessment->id)
            ->when($validated['id'] ?? null, fn($q) => $q->where('id', $validated['id']))
            ->first();

        if (! $question) {
            $nextOrder = ($assessment->questions()->max('order') ?? 0) + 1;
            $question = new NationalQuestion([
                'assessment_id' => $assessment->id,
                'order' => $nextOrder,
            ]);
        }

        $question->question_type = $validated['question_type'];
        $question->question_text = $validated['question_text'];
        $question->points = $validated['points'];
        $question->topic = $validated['topic'] ?? null;

        // Handle base64 image upload
        if (! empty($validated['image']) && str_starts_with($validated['image'], 'data:image')) {
            $data = explode(',', $validated['image'], 2)[1] ?? null;
            if ($data) {
                $binary = base64_decode($data);
                $path = 'national-questions/' . uniqid() . '_' . time() . '.png';
                Storage::disk('public')->put($path, $binary);
                $question->image_path = $path;
            }
        }

        $question->save();

        // Sync choices
        if (in_array($question->question_type, ['multiple_choice', 'multiple_select'])) {
            $choiceOrder = 1;
            $question->choices()->delete();
            foreach ($validated['choices'] ?? [] as $choice) {
                $question->choices()->create([
                    'choice_text' => $choice['choice_text'],
                    'is_correct' => (bool) $choice['is_correct'],
                    'order' => $choiceOrder++,
                ]);
            }
        } elseif ($question->question_type === 'true_false') {
            $question->choices()->delete();
            $answer = (bool) ($validated['answer'] ?? true);
            $question->choices()->createMany([
                ['choice_text' => 'True', 'is_correct' => $answer === true, 'order' => 1],
                ['choice_text' => 'False', 'is_correct' => $answer === false, 'order' => 2],
            ]);
        }

        // Recompute total points
        $total = $assessment->questions()->sum('points');
        $assessment->update(['total_points' => $total]);

        return back()->with('question', [
            'id' => $question->id,
        ]);
    }

    /**
     * Delete a question from a national assessment.
     */
    public function deleteQuestion(NationalAssessment $assessment, NationalQuestion $question): RedirectResponse
    {
        if ($question->assessment_id !== $assessment->id) {
            abort(404);
        }

        $question->choices()->delete();
        $question->delete();

        // Recompute total points
        $total = $assessment->questions()->sum('points');
        $assessment->update(['total_points' => $total]);

        return back()->with('success', 'Question deleted');
    }
    /**
     * Display draft in-service exams (unpublished).
     */
    public function drafts(Request $request): Response
    {
        $query = NationalAssessment::query()
            ->where('is_published', false)
            ->with(['creator:id,name'])
            ->withCount('questions')
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
            })
            ->orderBy($request->input('sort', 'created_at'), $request->input('direction', 'desc'));

        $drafts = $query->paginate(15)
            ->withQueryString()
            ->through(function (NationalAssessment $assessment) {
                return [
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
                    'created_by' => $assessment->creator?->name,
                    'created_at' => $assessment->created_at?->format('Y-m-d'),
                ];
            });

        return Inertia::render('inservice-exams/drafts', [
            'exams' => $drafts,
            'filters' => $request->only(['search']),
        ]);
    }
    /**
     * Display the specified national assessment.
     */
    public function show(NationalAssessment $assessment): Response
    {
        $assessment->load(['questions.choices', 'creator:id,name']);

        return Inertia::render('assessments/show', [
            'assessment' => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'description' => $assessment->description,
                'exam_year' => $assessment->exam_year,
                'exam_period' => $assessment->exam_period,
                'category' => $assessment->category,
                'duration_minutes' => $assessment->duration_minutes,
                'total_points' => $assessment->total_points,
                'passing_score' => $assessment->passing_score,
                'is_published' => $assessment->is_published,
                'questions' => $assessment->questions->map(fn($q) => [
                    'id' => $q->id,
                    'question_type' => $q->question_type,
                    'question_text' => $q->question_text,
                    'points' => $q->points,
                    'explanation' => $q->explanation,
                    'order' => $q->order,
                    'choices' => $q->choices->map(fn($c) => [
                        'id' => $c->id,
                        'choice_text' => $c->choice_text,
                        'is_correct' => $c->is_correct,
                        'order' => $c->order,
                    ]),
                ]),
                'created_by' => $assessment->creator?->name,
                'created_at' => $assessment->created_at?->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Update the specified national assessment.
     */
    public function update(Request $request, NationalAssessment $assessment): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'exam_year' => ['required', 'integer', 'min:2000', 'max:3000'],
            'exam_period' => ['required', 'string', 'max:100'],
            'category' => ['required', 'in:anatomic-pathology-theoretical,anatomic-pathology-projection,clinical-pathology-theoretical,clinical-pathology-projection'],
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
            'results_release_date' => ['nullable', 'date'],
        ]);

        $assessment->update($validated);

        return back()->with('success', 'Assessment updated successfully');
    }

    /**
     * Remove the specified national assessment.
     */
    public function destroy(NationalAssessment $assessment): RedirectResponse
    {
        if ($assessment->attempts()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete an exam that has attempts.']);
        }

        $assessment->delete();

        return redirect()->route('in-service.index')->with('success', 'Assessment deleted successfully');
    }
}
