<?php

namespace App\Http\Controllers;

use App\Exports\QuestionsTemplateExport;
use App\Imports\QuestionsImport;
use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionQuestion;
use App\Repositories\Contracts\InstitutionAssessmentRepositoryInterface;
use App\Repositories\Contracts\InstitutionQuestionChoiceRepositoryInterface;
use App\Repositories\Contracts\InstitutionQuestionRepositoryInterface;
use App\Services\InstitutionAssessmentQuestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InstitutionExamController extends Controller
{
    public function __construct(
        private readonly InstitutionAssessmentRepositoryInterface $assessmentRepository,
        private readonly InstitutionQuestionRepositoryInterface $questionRepository,
        private readonly InstitutionQuestionChoiceRepositoryInterface $questionChoiceRepository,
        private readonly InstitutionAssessmentQuestionService $questionService,
    ) {}

    /**
     * Display a listing of institution assessments.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user->currentOrganization?->type === 'national') {
            return redirect()->route('inservice-exams.active');
        }

        $assessments = $this->assessmentRepository
            ->paginateByPublication($user->current_organization_id, true, $request->only(['search', 'sort', 'direction']))
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
        if ($user->currentOrganization?->type === 'national') {
            return redirect()->route('inservice-exams.drafts');
        }

        $assessments = $this->assessmentRepository
            ->paginateByPublication($user->current_organization_id, false, $request->only(['search', 'sort', 'direction']))
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
            $isNationalContext = $request->user()->currentOrganization?->type === 'national';

            if ($isNationalContext) {
                return redirect()
                    ->route('inservice-exams.create')
                    ->with('error', 'Use the In-Service Exams flow for national assessments.');
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
                'available_from' => ['nullable', 'date'],
                'available_until' => ['nullable', 'date', 'after:available_from'],
                'is_published' => ['boolean'],
            ]);

            $assessment = $this->assessmentRepository->create([
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

            Log::info('Exam created successfully', ['id' => $assessment->id]);

            return redirect()
                ->route('institution-exams.edit', $assessment)
                ->with([
                    'success' => 'Exam created successfully! Now add questions.',
                ]);
        } catch (\Exception $e) {
            Log::error('Error creating exam: ' . $e->getMessage());

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

        $assessment = $this->assessmentRepository->loadForEdit($assessment);

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
            'questionBankScope' => $assessment->organization?->type === 'national' ? 'national' : 'institution',
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

        $assessment = $this->assessmentRepository->loadForShow($assessment);

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

        $isNationalOrgAssessment = $assessment->organization?->type === 'national';

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

        if ($isNationalOrgAssessment) {
            $validated['exam_category'] = 'In-service';
        }

        $this->assessmentRepository->update($assessment, $validated);

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
        if ($this->assessmentRepository->attemptsCount($assessment) > 0) {
            return back()->withErrors([
                'error' => 'Cannot delete assessment that has been attempted by residents.',
            ]);
        }

        $this->assessmentRepository->delete($assessment);

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

        $assessment = $this->assessmentRepository->loadQuestionsWithChoices($assessment);

        $duplicate = DB::transaction(function () use ($assessment, $request, $validated) {
            $duplicate = $this->assessmentRepository->create([
                'organization_id' => $assessment->organization_id,
                'created_by' => $request->user()->id,
                'title' => $validated['title'] ?? $this->generateDuplicateTitle($assessment->title, $assessment->organization_id),
                'description' => $assessment->description,
                'exam_category' => $assessment->exam_category,
                'course_id' => $assessment->course_id,
                'duration_minutes' => $assessment->duration_minutes,
                'total_points' => 0,
                'passing_score' => $assessment->passing_score,
                'randomize_questions' => $assessment->randomize_questions,
                'randomize_choices' => $assessment->randomize_choices,
                'show_results_immediately' => $assessment->show_results_immediately,
                'allow_review' => $assessment->allow_review,
                'available_from' => null,
                'available_until' => null,
                'is_published' => false,
            ]);

            $totalPoints = 0;

            foreach ($assessment->questions as $question) {
                $newQuestion = $this->questionRepository->createForAssessment($duplicate, [
                    'topic_id' => $question->topic_id,
                    'question_type' => $question->question_type,
                    'question_text' => $question->question_text,
                    'points' => $question->points,
                    'explanation' => $question->explanation,
                    'image_path' => $question->image_path,
                    'order' => $question->order,
                ]);

                $this->questionChoiceRepository->createMany(
                    $newQuestion,
                    $question->choices->map(fn($choice) => [
                        'choice_text' => $choice->choice_text,
                        'is_correct' => $choice->is_correct,
                        'order' => $choice->order,
                    ])->all()
                );

                $totalPoints += $newQuestion->points;
            }

            $this->assessmentRepository->update($duplicate, ['total_points' => $totalPoints]);

            return $duplicate;
        });

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

        $this->questionService->storeQuestions($assessment, $validated['questions']);

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

        $question = $this->questionService->saveQuestion(
            $assessment,
            $validated,
            $request->user(),
        );

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

        $this->questionService->deleteQuestion($assessment, $question);

        return back()->with('success', 'Question deleted successfully');
    }

    /**
     * Delete multiple questions from an assessment.
     */
    public function bulkDeleteQuestions(Request $request, InstitutionAssessment $assessment): RedirectResponse
    {
        if ($assessment->organization_id !== $request->user()->current_organization_id) {
            abort(403, 'You do not have access to this assessment.');
        }

        $validated = $request->validate([
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['required', 'integer'],
        ]);

        $questionIds = collect($validated['question_ids'])->map(fn ($id) => (int) $id)->unique()->values();

        $matchingCount = $assessment->questions()
            ->whereIn('id', $questionIds)
            ->count();

        if ($matchingCount !== $questionIds->count()) {
            abort(403, 'One or more selected questions do not belong to this assessment.');
        }

        $count = $this->questionService->deleteQuestions($assessment, $questionIds->all());

        return back()->with(
            'success',
            $count === 1 ? 'Question deleted successfully' : "Deleted {$count} questions successfully"
        );
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
            $import = new QuestionsImport($assessment->id, $assessment->organization_id, $request->user());
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

        $result = $this->questionService->addFromBank($assessment, $request->question_ids);
        $addedCount = $result['added_count'];
        $skippedCount = $result['skipped_count'];

        if ($addedCount === 0 && $skippedCount === 0) {
            return back()->with('error', 'No valid questions found.');
        }

        if ($addedCount === 0 && $skippedCount > 0) {
            return back()->with('warning', 'All selected questions are already in this exam.');
        }

        $message = "Successfully added {$addedCount} question(s) from question bank!";
        if ($skippedCount > 0) {
            $message .= " Skipped {$skippedCount} duplicate question(s).";
        }

        return back()->with('success', $message);
    }

    private function generateDuplicateTitle(string $originalTitle, int $organizationId): string
    {
        $baseTitle = $originalTitle . ' (Copy)';
        $candidate = $baseTitle;
        $suffix = 2;

        while ($this->assessmentRepository->titleExists($organizationId, $candidate)) {
            $candidate = $originalTitle . ' (Copy ' . $suffix . ')';
            $suffix++;
        }

        return $candidate;
    }

}
