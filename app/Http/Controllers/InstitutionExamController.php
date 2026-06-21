<?php

namespace App\Http\Controllers;

use App\Exports\QuestionsTemplateExport;
use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionQuestion;
use App\Services\InstitutionAssessmentDuplicationService;
use App\Services\InstitutionAssessmentImportService;
use App\Services\InstitutionAssessmentManagementService;
use App\Services\InstitutionAssessmentQuestionService;
use App\Services\InstitutionAssessmentReadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InstitutionExamController extends Controller
{
    public function __construct(
        private readonly InstitutionAssessmentDuplicationService $duplicationService,
        private readonly InstitutionAssessmentImportService $importService,
        private readonly InstitutionAssessmentManagementService $managementService,
        private readonly InstitutionAssessmentQuestionService $questionService,
        private readonly InstitutionAssessmentReadService $readService,
    ) {}

    /**
     * Display a listing of institution assessments.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($this->readService->shouldRedirectToInservice($user)) {
            return redirect()->route('inservice-exams.active');
        }

        $assessments = $this->readService->listForPublication(
            $user,
            true,
            $request->only(['search', 'sort', 'direction']),
        );

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
        if ($this->readService->shouldRedirectToInservice($user)) {
            return redirect()->route('inservice-exams.drafts');
        }

        $assessments = $this->readService->listForPublication(
            $user,
            false,
            $request->only(['search', 'sort', 'direction']),
        );

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
            if (! $this->managementService->canCreateFromCurrentOrganization($request->user())) {
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

            $assessment = $this->managementService->create($request->user(), $validated);

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

        return Inertia::render('institution-exams/edit', $this->readService->editPayload($assessment));
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

        return Inertia::render('assessments/show', $this->readService->showPayload($assessment));
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

        $this->managementService->update($assessment, $validated);

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

        if (! $this->managementService->canDelete($assessment)) {
            return back()->withErrors([
                'error' => 'Cannot delete assessment that has been attempted by residents.',
            ]);
        }

        $this->managementService->delete($assessment);

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

        $duplicate = $this->duplicationService->duplicate(
            $assessment,
            $request->user()->id,
            $validated['title'] ?? null,
        );

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
                'image_url' => $question->image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($question->image_path) : null,
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
            $result = $this->importService->importQuestions(
                $assessment,
                $request->file('file'),
                $request->user(),
            );

            return back()->with($result['status'], $result['message']);
        } catch (\Exception $e) {
            return back()->withErrors($this->importService->formatImportFailure($e));
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

}
