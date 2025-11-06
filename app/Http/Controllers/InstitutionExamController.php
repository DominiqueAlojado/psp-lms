<?php

namespace App\Http\Controllers;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionExamController extends Controller
{
    /**
     * Display a listing of institution assessments.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $organizationId = $user->current_organization_id;

        $assessments = InstitutionAssessment::query()
            ->where('organization_id', $organizationId)
            ->where('is_published', true) // Only published exams
            ->with(['questions', 'creator:id,name'])
            ->withCount('questions')
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy($request->input('sort', 'created_at'), $request->input('direction', 'desc'))
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($assessment) => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'description' => $assessment->description,
                'questions_count' => $assessment->questions_count,
                'total_points' => $assessment->total_points,
                'passing_score' => $assessment->passing_score,
                'duration_minutes' => $assessment->duration_minutes,
                'is_published' => $assessment->is_published,
                'is_available' => $assessment->isAvailable(),
                'available_from' => $assessment->available_from?->format('Y-m-d H:i'),
                'available_until' => $assessment->available_until?->format('Y-m-d H:i'),
                'created_by' => $assessment->creator->name,
                'created_at' => $assessment->created_at->format('Y-m-d'),
                'updated_at' => $assessment->updated_at->diffForHumans(),
            ]);

        return Inertia::render('institution-exams/active', [
            'exams' => $assessments,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Display a listing of draft institution assessments.
     */
    public function drafts(Request $request): Response
    {
        $user = $request->user();
        $organizationId = $user->current_organization_id;

        $assessments = InstitutionAssessment::query()
            ->where('organization_id', $organizationId)
            ->where('is_published', false) // Only draft exams
            ->with(['questions', 'creator:id,name'])
            ->withCount('questions')
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy($request->input('sort', 'created_at'), $request->input('direction', 'desc'))
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($assessment) => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'description' => $assessment->description,
                'questions_count' => $assessment->questions_count,
                'total_points' => $assessment->total_points,
                'passing_score' => $assessment->passing_score,
                'duration_minutes' => $assessment->duration_minutes,
                'is_published' => $assessment->is_published,
                'is_available' => $assessment->isAvailable(),
                'available_from' => $assessment->available_from?->format('Y-m-d H:i'),
                'available_until' => $assessment->available_until?->format('Y-m-d H:i'),
                'created_by' => $assessment->creator->name,
                'created_at' => $assessment->created_at->format('Y-m-d'),
                'updated_at' => $assessment->updated_at->diffForHumans(),
            ]);

        return Inertia::render('institution-exams/drafts', [
            'exams' => $assessments,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Store a newly created assessment.
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'duration_minutes' => ['nullable', 'integer', 'min:1'],
                'passing_score' => ['required', 'integer', 'min:0'],
                'randomize_questions' => ['boolean'],
                'randomize_choices' => ['boolean'],
                'show_results_immediately' => ['boolean'],
                'allow_review' => ['boolean'],
                'available_from' => ['nullable', 'date'],
                'available_until' => ['nullable', 'date', 'after:available_from'],
            ]);

            $assessment = InstitutionAssessment::create([
                'organization_id' => $request->user()->current_organization_id,
                'created_by' => $request->user()->id,
                'total_points' => 0,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'duration_minutes' => $validated['duration_minutes'] ?? null,
                'passing_score' => $validated['passing_score'],
                'randomize_questions' => $validated['randomize_questions'] ?? false,
                'randomize_choices' => $validated['randomize_choices'] ?? false,
                'show_results_immediately' => $validated['show_results_immediately'] ?? true,
                'allow_review' => $validated['allow_review'] ?? true,
                'available_from' => $validated['available_from'] ?? null,
                'available_until' => $validated['available_until'] ?? null,
            ]);

            \Log::info('Exam created successfully', ['id' => $assessment->id]);

            return redirect("/institution-exams/create?assessment_id={$assessment->id}")->with([
                'success' => 'Exam created successfully! Now add questions.',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error creating exam: '.$e->getMessage());

            return back()->withErrors(['error' => 'Failed to create exam: '.$e->getMessage()]);
        }
    }

    /**
     * Show the form for editing the specified assessment.
     */
    public function edit(InstitutionAssessment $assessment): Response
    {
        // Verify user has access to this assessment
        if ($assessment->organization_id !== auth()->user()->current_organization_id) {
            abort(403, 'You do not have access to this assessment.');
        }

        $assessment->load(['questions.choices', 'creator:id,name']);

        return Inertia::render('institution-exams/edit', [
            'assessment' => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'description' => $assessment->description,
                'duration_minutes' => $assessment->duration_minutes,
                'total_points' => $assessment->total_points,
                'passing_score' => $assessment->passing_score,
                'randomize_questions' => $assessment->randomize_questions,
                'randomize_choices' => $assessment->randomize_choices,
                'show_results_immediately' => $assessment->show_results_immediately,
                'allow_review' => $assessment->allow_review,
                'is_published' => $assessment->is_published,
                'available_from' => $assessment->available_from?->format('Y-m-d\TH:i'),
                'available_until' => $assessment->available_until?->format('Y-m-d\TH:i'),
                'questions' => $assessment->questions->map(fn ($q) => [
                    'id' => $q->id,
                    'topic_id' => $q->topic_id,
                    'question_type' => $q->question_type,
                    'question_text' => $q->question_text,
                    'points' => $q->points,
                    'explanation' => $q->explanation,
                    'image_path' => $q->image_path,
                    'image_url' => $q->image_path ? Storage::disk('public')->url($q->image_path) : null,
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
     * Display the specified assessment.
     */
    public function show(InstitutionAssessment $assessment): Response
    {
        // Verify user has access to this assessment
        if ($assessment->organization_id !== auth()->user()->current_organization_id) {
            abort(403, 'You do not have access to this assessment.');
        }

        $assessment->load(['questions.choices', 'creator:id,name']);

        return Inertia::render('assessments/show', [
            'assessment' => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'description' => $assessment->description,
                'duration_minutes' => $assessment->duration_minutes,
                'total_points' => $assessment->total_points,
                'passing_score' => $assessment->passing_score,
                'is_published' => $assessment->is_published,
                'questions' => $assessment->questions->map(fn ($q) => [
                    'id' => $q->id,
                    'question_type' => $q->question_type,
                    'question_text' => $q->question_text,
                    'points' => $q->points,
                    'explanation' => $q->explanation,
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
     * Update the specified assessment.
     */
    public function update(Request $request, InstitutionAssessment $assessment): RedirectResponse
    {
        // Verify user has access
        if ($assessment->organization_id !== $request->user()->current_organization_id) {
            abort(403, 'You do not have access to this assessment.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'passing_score' => ['required', 'integer', 'min:0'],
            'randomize_questions' => ['boolean'],
            'randomize_choices' => ['boolean'],
            'show_results_immediately' => ['boolean'],
            'allow_review' => ['boolean'],
            'is_published' => ['boolean'],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['nullable', 'date', 'after:available_from'],
        ]);

        $assessment->update($validated);

        return back()->with('success', 'Assessment updated successfully');
    }

    /**
     * Remove the specified assessment.
     */
    public function destroy(InstitutionAssessment $assessment): RedirectResponse
    {
        // Verify user has access
        if ($assessment->organization_id !== auth()->user()->current_organization_id) {
            abort(403, 'You do not have access to this assessment.');
        }

        // Check if there are any attempts
        if ($assessment->attempts()->count() > 0) {
            return back()->withErrors([
                'error' => 'Cannot delete assessment that has been attempted by residents.',
            ]);
        }

        $assessment->delete();

        return back()->with('success', 'Assessment deleted successfully');
    }

    /**
     * Store questions for an assessment (MCQ, multiple_select, true_false).
     */
    public function storeQuestions(Request $request, InstitutionAssessment $assessment): RedirectResponse
    {
        // Verify user has access
        if ($assessment->organization_id !== $request->user()->current_organization_id) {
            abort(403, 'You do not have access to this assessment.');
        }

        $validated = $request->validate([
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.id' => ['nullable', 'integer', 'exists:institution_questions,id'],
            'questions.*.topic_id' => ['nullable', 'integer', 'exists:topics,id'],
            'questions.*.question_type' => ['required', 'in:multiple_choice,multiple_select,true_false'],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.points' => ['required', 'integer', 'min:1'],
            'questions.*.order' => ['nullable', 'integer', 'min:0'],
            'questions.*.image' => ['nullable', 'string'], // base64 encoded image
            'questions.*.choices' => ['nullable', 'array'],
            'questions.*.choices.*.choice_text' => ['required_with:questions.*.choices', 'string'],
            'questions.*.choices.*.is_correct' => ['required_with:questions.*.choices', 'boolean'],
            'questions.*.answer' => ['nullable'], // for true_false
        ]);

        $existingQuestionIds = [];
        $totalPoints = 0;

        foreach ($validated['questions'] as $q) {
            $imagePath = null;

            // Handle base64 image upload if provided
            if (! empty($q['image'])) {
                try {
                    // Extract base64 data
                    $imageData = $q['image'];
                    if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                        $imageData = substr($imageData, strpos($imageData, ',') + 1);
                        $type = strtolower($type[1]); // jpg, png, gif

                        $imageData = base64_decode($imageData);
                        if ($imageData !== false) {
                            $filename = 'question_'.uniqid().'.'.$type;
                            $path = 'question-images/'.$filename;
                            Storage::disk('public')->put($path, $imageData);
                            $imagePath = $path;
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Error uploading question image: '.$e->getMessage());
                }
            }

            $questionData = [
                'question_type' => $q['question_type'],
                'topic_id' => $q['topic_id'] ?? null,
                'question_text' => $q['question_text'],
                'points' => $q['points'],
                'order' => $q['order'] ?? 0,
            ];

            if ($imagePath) {
                $questionData['image_path'] = $imagePath;
            }

            // Update existing question or create new one
            if (! empty($q['id'])) {
                $question = $assessment->questions()->find($q['id']);
                if ($question) {
                    $question->update($questionData);
                    $existingQuestionIds[] = $question->id;
                    // Delete old choices to recreate them
                    $question->choices()->delete();
                }
            } else {
                $question = $assessment->questions()->create($questionData);
                $existingQuestionIds[] = $question->id;
            }

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

            // True/False stored as two choices for consistency
            if ($q['question_type'] === 'true_false') {
                $answer = filter_var($q['answer'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $question->choices()->createMany([
                    ['choice_text' => 'True', 'is_correct' => $answer === true, 'order' => 0],
                    ['choice_text' => 'False', 'is_correct' => $answer === false, 'order' => 1],
                ]);
            }

            $totalPoints += $q['points'];
        }

        // Delete questions that are no longer in the list
        $assessment->questions()->whereNotIn('id', $existingQuestionIds)->delete();

        // Update total points on assessment
        $assessment->update(['total_points' => $totalPoints]);

        return back()->with('success', 'Questions saved successfully');
    }

    /**
     * Save or update a single question.
     */
    public function saveOneQuestion(Request $request, InstitutionAssessment $assessment): RedirectResponse
    {
        // Verify user has access
        if ($assessment->organization_id !== $request->user()->current_organization_id) {
            abort(403, 'You do not have access to this assessment.');
        }

        $validated = $request->validate([
            'id' => ['nullable', 'integer', 'exists:institution_questions,id'],
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
            'question_type' => ['required', 'in:multiple_choice,multiple_select,true_false'],
            'question_text' => ['required', 'string'],
            'points' => ['required', 'integer', 'min:1'],
            'order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'string'], // base64 encoded image
            'choices' => ['nullable', 'array'],
            'choices.*.choice_text' => ['required_with:choices', 'string'],
            'choices.*.is_correct' => ['required_with:choices', 'boolean'],
            'answer' => ['nullable'], // for true_false
        ]);

        $imagePath = null;

        // Handle base64 image upload if provided
        if (! empty($validated['image'])) {
            try {
                $imageData = $validated['image'];
                if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                    $imageData = substr($imageData, strpos($imageData, ',') + 1);
                    $type = strtolower($type[1]);

                    $imageData = base64_decode($imageData);
                    if ($imageData !== false) {
                        $filename = 'question_'.uniqid().'.'.$type;
                        $path = 'question-images/'.$filename;
                        Storage::disk('public')->put($path, $imageData);
                        $imagePath = $path;
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Error uploading question image: '.$e->getMessage());
            }
        }

        $questionData = [
            'question_type' => $validated['question_type'],
            'topic_id' => $validated['topic_id'] ?? null,
            'question_text' => $validated['question_text'],
            'points' => $validated['points'],
            'order' => $validated['order'] ?? 0,
        ];

        if ($imagePath) {
            $questionData['image_path'] = $imagePath;
        }

        // Update existing question or create new one
        if (! empty($validated['id'])) {
            $question = $assessment->questions()->find($validated['id']);
            if ($question) {
                $question->update($questionData);
                $question->choices()->delete();
            }
        } else {
            $question = $assessment->questions()->create($questionData);
        }

        // Handle choices for MCQ and Multiple Select
        if (in_array($validated['question_type'], ['multiple_choice', 'multiple_select'])) {
            foreach ($validated['choices'] ?? [] as $idx => $c) {
                $question->choices()->create([
                    'choice_text' => $c['choice_text'],
                    'is_correct' => (bool) ($c['is_correct'] ?? false),
                    'order' => $idx,
                ]);
            }
        }

        // True/False stored as two choices for consistency
        if ($validated['question_type'] === 'true_false') {
            $answer = filter_var($validated['answer'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $question->choices()->createMany([
                ['choice_text' => 'True', 'is_correct' => $answer === true, 'order' => 0],
                ['choice_text' => 'False', 'is_correct' => $answer === false, 'order' => 1],
            ]);
        }

        // Recalculate total points
        $totalPoints = $assessment->questions()->sum('points');
        $assessment->update(['total_points' => $totalPoints]);

        return back()->with([
            'success' => 'Question saved successfully',
            'question' => [
                'id' => $question->id,
                'topic_id' => $question->topic_id,
                'question_type' => $question->question_type,
                'question_text' => $question->question_text,
                'points' => $question->points,
                'order' => $question->order,
                'image_path' => $question->image_path,
                'image_url' => $question->image_path ? Storage::disk('public')->url($question->image_path) : null,
            ],
        ]);
    }

    /**
     * Delete a single question.
     */
    public function deleteQuestion(Request $request, InstitutionAssessment $assessment, InstitutionQuestion $question): RedirectResponse
    {
        // Verify user has access
        if ($assessment->organization_id !== $request->user()->current_organization_id) {
            abort(403, 'You do not have access to this assessment.');
        }

        // Verify question belongs to this assessment
        if ($question->assessment_id !== $assessment->id) {
            abort(403, 'This question does not belong to this assessment.');
        }

        $question->delete();

        // Recalculate total points
        $totalPoints = $assessment->questions()->sum('points');
        $assessment->update(['total_points' => $totalPoints]);

        return back()->with('success', 'Question deleted successfully');
    }
}
