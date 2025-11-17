<?php

namespace App\Http\Controllers;

use App\Exports\QuestionsTemplateExport;
use App\Imports\QuestionsImport;
use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionQuestion;
use App\Models\Institution\InstitutionQuestionChoice;
use App\Models\QuestionBank;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
            ->through(fn($assessment) => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'description' => $assessment->description,
                'exam_category' => $assessment->exam_category,
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
            ->through(fn($assessment) => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'description' => $assessment->description,
                'exam_category' => $assessment->exam_category,
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
                'exam_category' => ['nullable', 'string', 'max:255'],
                'duration_minutes' => ['nullable', 'integer', 'min:1'],
                'passing_score' => ['required', 'integer', 'min:0'],
                'randomize_questions' => ['boolean'],
                'randomize_choices' => ['boolean'],
                'show_results_immediately' => ['boolean'],
                'allow_review' => ['boolean'],
                'available_from' => ['nullable', 'date'],
                'available_until' => ['nullable', 'date', 'after:available_from'],
                'is_published' => ['boolean'],
            ]);

            $assessment = InstitutionAssessment::create([
                'organization_id' => $request->user()->current_organization_id,
                'created_by' => $request->user()->id,
                'total_points' => 0,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'exam_category' => $validated['exam_category'] ?? null,
                'duration_minutes' => $validated['duration_minutes'] ?? null,
                'passing_score' => $validated['passing_score'],
                'randomize_questions' => $validated['randomize_questions'] ?? false,
                'randomize_choices' => $validated['randomize_choices'] ?? false,
                'show_results_immediately' => $validated['show_results_immediately'] ?? true,
                'allow_review' => $validated['allow_review'] ?? true,
                'available_from' => $validated['available_from'] ?? null,
                'available_until' => $validated['available_until'] ?? null,
                'is_published' => $validated['is_published'] ?? false,
            ]);

            \Log::info('Exam created successfully', ['id' => $assessment->id]);

            return redirect()
                ->route('institution-exams.edit', $assessment)
                ->with([
                    'success' => 'Exam created successfully! Now add questions.',
                ]);
        } catch (\Exception $e) {
            \Log::error('Error creating exam: ' . $e->getMessage());

            return back()->withErrors(['error' => 'Failed to create exam: ' . $e->getMessage()]);
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
                'exam_category' => $assessment->exam_category,
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
                'questions' => $assessment->questions->map(fn($q) => [
                    'id' => $q->id,
                    'topic_id' => $q->topic_id,
                    'question_type' => $q->question_type,
                    'question_text' => $q->question_text,
                    'points' => $q->points,
                    'explanation' => $q->explanation,
                    'image_path' => $q->image_path,
                    'image_url' => $q->image_path ? Storage::disk('public')->url($q->image_path) : null,
                    'order' => $q->order,
                    'choices' => $q->choices->map(fn($c) => [
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
            'exam_category' => ['nullable', 'string', 'max:255'],
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
     * Duplicate an existing assessment and its questions.
     */
    public function duplicate(Request $request, InstitutionAssessment $assessment): RedirectResponse
    {
        if ($assessment->organization_id !== $request->user()->current_organization_id) {
            abort(403, 'You do not have access to this assessment.');
        }

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $assessment->load('questions.choices');

        $duplicate = $assessment->replicate();
        $duplicate->title = $validated['title'] ?? $this->generateDuplicateTitle($assessment->title, $assessment->organization_id);
        $duplicate->is_published = false;
        $duplicate->available_from = null;
        $duplicate->available_until = null;
        $duplicate->created_by = $request->user()->id;
        $duplicate->total_points = 0;
        $duplicate->save();

        $totalPoints = 0;

        foreach ($assessment->questions as $question) {
            $newQuestion = $question->replicate();
            $newQuestion->assessment_id = $duplicate->id;
            $newQuestion->save();

            foreach ($question->choices as $choice) {
                $newQuestion->choices()->create([
                    'choice_text' => $choice->choice_text,
                    'is_correct' => $choice->is_correct,
                    'order' => $choice->order,
                ]);
            }

            $totalPoints += $newQuestion->points;
        }

        $duplicate->update(['total_points' => $totalPoints]);

        return redirect()
            ->route('institution-exams.edit', $duplicate)
            ->with('success', 'Exam duplicated successfully. You can now make changes.');
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
                            $filename = 'question_' . uniqid() . '.' . $type;
                            $path = 'question-images/' . $filename;
                            Storage::disk('public')->put($path, $imageData);
                            $imagePath = $path;
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Error uploading question image: ' . $e->getMessage());
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
                        $filename = 'question_' . uniqid() . '.' . $type;
                        $path = 'question-images/' . $filename;
                        Storage::disk('public')->put($path, $imageData);
                        $imagePath = $path;
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Error uploading question image: ' . $e->getMessage());
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

        $isNewQuestion = empty($validated['id']);

        // Update existing question or create new one
        if (! $isNewQuestion) {
            $question = $assessment->questions()->find($validated['id']);
            if ($question) {
                $question->update($questionData);
                $question->choices()->delete();
            }
        } else {
            $question = $assessment->questions()->create($questionData);
        }

        // Handle choices for MCQ and Multiple Select
        $choicesData = [];
        if (in_array($validated['question_type'], ['multiple_choice', 'multiple_select'])) {
            foreach ($validated['choices'] ?? [] as $idx => $c) {
                $choice = $question->choices()->create([
                    'choice_text' => $c['choice_text'],
                    'is_correct' => (bool) ($c['is_correct'] ?? false),
                    'order' => $idx,
                ]);
                $choicesData[] = [
                    'choice_text' => $c['choice_text'],
                    'is_correct' => (bool) ($c['is_correct'] ?? false),
                ];
            }
        }

        // True/False stored as two choices for consistency
        if ($validated['question_type'] === 'true_false') {
            $answer = filter_var($validated['answer'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $question->choices()->createMany([
                ['choice_text' => 'True', 'is_correct' => $answer === true, 'order' => 0],
                ['choice_text' => 'False', 'is_correct' => $answer === false, 'order' => 1],
            ]);
            $choicesData = [
                ['choice_text' => 'True', 'is_correct' => $answer === true],
                ['choice_text' => 'False', 'is_correct' => $answer === false],
            ];
        }

        // Save to question bank if this is a new question
        if ($isNewQuestion) {
            $this->saveToQuestionBank($question, $assessment, $choicesData, $imagePath, $request->user());
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

    /**
     * Download Excel template for bulk question import.
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        return Excel::download(new QuestionsTemplateExport, 'question_import_template.xlsx');
    }

    /**
     * Import questions from Excel file.
     */
    public function importQuestions(Request $request, InstitutionAssessment $assessment): RedirectResponse
    {
        // Verify user has access
        if ($assessment->organization_id !== $request->user()->current_organization_id) {
            abort(403, 'You do not have access to this assessment.');
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'], // 5MB max
        ]);

        try {
            $import = new QuestionsImport($assessment->id, $assessment->organization_id);
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $errors = $import->getErrors();

            if (count($errors) > 0) {
                $errorMessage = "Imported {$successCount} questions with " . count($errors) . ' errors: ' . implode('; ', array_slice($errors, 0, 3));
                if (count($errors) > 3) {
                    $errorMessage .= '... and ' . (count($errors) - 3) . ' more errors.';
                }

                return back()->with('warning', $errorMessage);
            }

            return back()->with('success', "Successfully imported {$successCount} questions!");
        } catch (\Exception $e) {
            \Log::error('Question import failed', ['error' => $e->getMessage()]);

            return back()->withErrors(['file' => 'Import failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Add questions from question bank to assessment.
     */
    public function addFromBank(Request $request, InstitutionAssessment $assessment): RedirectResponse
    {
        // Verify user has access
        if ($assessment->organization_id !== $request->user()->current_organization_id) {
            abort(403, 'You do not have access to this assessment.');
        }

        $request->validate([
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['required', 'integer', 'exists:question_bank,id'],
        ]);

        $bankQuestions = QuestionBank::with('choices')
            ->whereIn('id', $request->question_ids)
            ->where('organization_id', $assessment->organization_id)
            ->get();

        if ($bankQuestions->isEmpty()) {
            return back()->with('error', 'No valid questions found.');
        }

        $addedCount = 0;

        foreach ($bankQuestions as $bankQuestion) {
            // Create a copy of the question in the assessment
            $question = InstitutionQuestion::create([
                'assessment_id' => $assessment->id,
                'topic_id' => $bankQuestion->topic_id,
                'question_type' => $bankQuestion->question_type,
                'question_text' => $bankQuestion->question_text,
                'points' => $bankQuestion->points,
                'explanation' => $bankQuestion->explanation,
                'image_path' => $bankQuestion->image_path, // Reuse the same image
            ]);

            // Copy choices
            foreach ($bankQuestion->choices as $bankChoice) {
                InstitutionQuestionChoice::create([
                    'question_id' => $question->id,
                    'choice_text' => $bankChoice->choice_text,
                    'is_correct' => $bankChoice->is_correct,
                    'order' => $bankChoice->order,
                ]);
            }

            // Increment usage counter in question bank
            $bankQuestion->incrementUsage();

            $addedCount++;
        }

        return back()->with('success', "Successfully added {$addedCount} questions from question bank!");
    }

    private function generateDuplicateTitle(string $originalTitle, int $organizationId): string
    {
        $baseTitle = $originalTitle . ' (Copy)';
        $candidate = $baseTitle;
        $suffix = 2;

        while (InstitutionAssessment::where('organization_id', $organizationId)->where('title', $candidate)->exists()) {
            $candidate = $originalTitle . ' (Copy ' . $suffix . ')';
            $suffix++;
        }

        return $candidate;
    }

    /**
     * Save a question created in an exam to the question bank.
     */
    private function saveToQuestionBank(
        InstitutionQuestion $question,
        InstitutionAssessment $assessment,
        array $choicesData,
        ?string $imagePath,
        $user
    ): void {
        try {
            // Create question in question bank
            $bankQuestion = QuestionBank::create([
                'organization_id' => $assessment->organization_id,
                'owner_type' => 'institution',
                'topic_id' => $question->topic_id,
                'created_by' => $user->id,
                'question_type' => $question->question_type,
                'question_text' => $question->question_text,
                'points' => $question->points,
                'image_path' => $imagePath,
                'is_approved' => false, // New questions need approval
            ]);

            // Create choices in question bank
            foreach ($choicesData as $idx => $choice) {
                $bankQuestion->choices()->create([
                    'choice_text' => $choice['choice_text'],
                    'is_correct' => $choice['is_correct'],
                    'order' => $idx,
                ]);
            }

            // Initialize statistics
            $bankQuestion->statistics()->create([
                'question_id' => $bankQuestion->id,
                'scope' => 'institution',
                'institution_id' => $assessment->organization_id,
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the question creation
            \Log::error('Failed to save question to question bank: ' . $e->getMessage());
        }
    }
}
