<?php

namespace App\Http\Controllers;

use App\Exports\QuestionsTemplateExport;
use App\Http\Requests\InstitutionExams\AddInstitutionQuestionsFromBankRequest;
use App\Http\Requests\InstitutionExams\BulkDeleteInstitutionQuestionsRequest;
use App\Http\Requests\InstitutionExams\DuplicateInstitutionAssessmentRequest;
use App\Http\Requests\InstitutionExams\ImportInstitutionAssessmentQuestionsRequest;
use App\Http\Requests\InstitutionExams\SaveInstitutionAssessmentQuestionRequest;
use App\Http\Requests\InstitutionExams\StoreInstitutionAssessmentQuestionsRequest;
use App\Http\Requests\InstitutionExams\StoreInstitutionAssessmentRequest;
use App\Http\Requests\InstitutionExams\UpdateInstitutionAssessmentRequest;
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
use Illuminate\Support\Facades\Storage;
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
    public function store(StoreInstitutionAssessmentRequest $request): RedirectResponse
    {
        try {
            if (! $this->managementService->canCreateFromCurrentOrganization($request->user())) {
                return redirect()
                    ->route('inservice-exams.create')
                    ->with('error', 'Use the In-Service Exams flow for national assessments.');
            }

            $assessment = $this->managementService->create($request->user(), $request->validated());

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
    public function update(UpdateInstitutionAssessmentRequest $request, InstitutionAssessment $assessment): RedirectResponse
    {
        // Verify user has access
        if ($assessment->organization_id !== $request->user()->current_organization_id) {
            abort(403, 'You do not have access to this assessment.');
        }

        $this->managementService->update($assessment, $request->validated());

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
    public function duplicate(DuplicateInstitutionAssessmentRequest $request, InstitutionAssessment $assessment): RedirectResponse
    {
        if ($assessment->organization_id !== $request->user()->current_organization_id) {
            abort(403, 'You do not have access to this assessment.');
        }

        $duplicate = $this->duplicationService->duplicate(
            $assessment,
            $request->user()->id,
            $request->validated('title'),
        );

        return redirect()
            ->route('institution-exams.edit', $duplicate)
            ->with('success', 'Exam duplicated successfully. You can now make changes.');
    }

    /**
     * Store questions for an assessment (MCQ, multiple_select, true_false).
     */
    public function storeQuestions(StoreInstitutionAssessmentQuestionsRequest $request, InstitutionAssessment $assessment): RedirectResponse
    {
        // Verify user has access
        if ($assessment->organization_id !== $request->user()->current_organization_id) {
            abort(403, 'You do not have access to this assessment.');
        }

        $this->questionService->storeQuestions($assessment, $request->validated('questions'));

        return back()->with('success', 'Questions saved successfully');
    }

    /**
     * Save or update a single question.
     */
    public function saveOneQuestion(SaveInstitutionAssessmentQuestionRequest $request, InstitutionAssessment $assessment): RedirectResponse
    {
        // Verify user has access
        if ($assessment->organization_id !== $request->user()->current_organization_id) {
            abort(403, 'You do not have access to this assessment.');
        }

        $question = $this->questionService->saveQuestion(
            $assessment,
            $request->validated(),
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
    public function bulkDeleteQuestions(BulkDeleteInstitutionQuestionsRequest $request, InstitutionAssessment $assessment): RedirectResponse
    {
        if ($assessment->organization_id !== $request->user()->current_organization_id) {
            abort(403, 'You do not have access to this assessment.');
        }

        $questionIds = collect($request->validated('question_ids'))->map(fn ($id) => (int) $id)->unique()->values();

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
    public function importQuestions(ImportInstitutionAssessmentQuestionsRequest $request, InstitutionAssessment $assessment): RedirectResponse
    {
        // Verify user has access
        if ($assessment->organization_id !== $request->user()->current_organization_id) {
            abort(403, 'You do not have access to this assessment.');
        }

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
    public function addFromBank(AddInstitutionQuestionsFromBankRequest $request, InstitutionAssessment $assessment): RedirectResponse
    {
        // Verify user has access
        if ($assessment->organization_id !== $request->user()->current_organization_id) {
            abort(403, 'You do not have access to this assessment.');
        }

        $result = $this->questionService->addFromBank($assessment, $request->validated('question_ids'));
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
