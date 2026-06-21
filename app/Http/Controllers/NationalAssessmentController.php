<?php

namespace App\Http\Controllers;

use App\Exports\QuestionsTemplateExport;
use App\Http\Requests\NationalAssessments\AddNationalQuestionsFromBankRequest;
use App\Http\Requests\NationalAssessments\DuplicateNationalAssessmentRequest;
use App\Http\Requests\NationalAssessments\ImportNationalAssessmentQuestionsRequest;
use App\Http\Requests\NationalAssessments\PreviewNationalAssessmentQuestionsRequest;
use App\Http\Requests\NationalAssessments\SaveNationalAssessmentQuestionRequest;
use App\Http\Requests\NationalAssessments\StoreNationalAssessmentQuestionsRequest;
use App\Http\Requests\NationalAssessments\StoreNationalAssessmentRequest;
use App\Http\Requests\NationalAssessments\UpdateNationalAssessmentRequest;
use App\Models\National\NationalAssessment;
use App\Models\National\NationalQuestion;
use App\Models\Topic;
use App\Services\ActivityLog\NationalAssessmentActivityLogService;
use App\Services\NationalAssessmentDuplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use App\Services\NationalAssessmentImportService;
use App\Services\NationalAssessmentManagementService;
use App\Services\NationalAssessmentQuestionService;
use App\Services\NationalAssessmentReadService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NationalAssessmentController extends Controller
{
    public function __construct(
        protected NationalAssessmentActivityLogService $activityLogService,
        private readonly NationalAssessmentReadService $readService,
        private readonly NationalAssessmentManagementService $managementService,
        private readonly NationalAssessmentQuestionService $questionService,
        private readonly NationalAssessmentDuplicationService $duplicationService,
        private readonly NationalAssessmentImportService $importService,
    ) {}
    /**
     * Display a listing of national in-service exams.
     */
    public function index(Request $request): Response
    {
        $assessments = $this->readService->list($request->only(['search', 'year', 'status', 'sort', 'direction']));
        $years = $this->readService->years();

        return Inertia::render('inservice-exams/index', [
            'assessments' => $assessments,
            'filters' => $request->only(['search', 'year', 'status']),
            'years' => $years,
        ]);
    }

    /**
     * Display active (published) in-service exams.
     */
    public function active(Request $request): Response
    {
        $assessments = $this->readService->active($request->only(['search', 'sort', 'direction']));

        Log::info('Active exams query result', [
            'count' => $assessments->count(),
            'total' => $assessments->total(),
            'data_count' => count($assessments->items()),
        ]);

        return Inertia::render('inservice-exams/active', [
            'exams' => $assessments,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Store a newly created national in-service exam.
     */
    public function store(StoreNationalAssessmentRequest $request): RedirectResponse
    {
        $assessment = $this->managementService->create($request->user(), $request->validated());

        return back()->with('assessment_id', $assessment->id);
    }

    /**
     * Store questions for a national in-service exam.
     */
    public function storeQuestions(StoreNationalAssessmentQuestionsRequest $request, NationalAssessment $assessment): RedirectResponse
    {
        $this->questionService->storeQuestions($assessment, $request->validated('questions'));

        return back()->with('success', 'Questions saved successfully.');
    }

    /**
     * Add questions from question bank to assessment.
     */
    public function addFromBank(AddNationalQuestionsFromBankRequest $request, NationalAssessment $assessment): RedirectResponse
    {
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

    /**
     * Edit page for a national assessment.
     */
    public function edit(NationalAssessment $assessment): Response
    {
        return Inertia::render('inservice-exams/edit', $this->readService->editPayload($assessment));
    }

    /**
     * Save or update a single question.
     */
    public function saveOneQuestion(SaveNationalAssessmentQuestionRequest $request, NationalAssessment $assessment): RedirectResponse
    {
        $question = $this->questionService->saveQuestion($assessment, $request->validated(), $request->user());

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

        $this->questionService->deleteQuestion($assessment, $question);

        return back()->with('success', 'Question deleted');
    }

    /**
     * Duplicate an existing national assessment and its questions.
     */
    public function duplicate(DuplicateNationalAssessmentRequest $request, NationalAssessment $assessment): RedirectResponse
    {
        $duplicate = $this->duplicationService->duplicate(
            $assessment,
            $request->user()->id,
            $request->validated('title'),
        );

        return redirect()
            ->route('inservice-exams.edit', $duplicate)
            ->with('success', 'Exam duplicated successfully. You can now make changes.');
    }

    /**
     * Preview questions from uploaded Excel file for national assessment.
     */
    public function previewQuestions(PreviewNationalAssessmentQuestionsRequest $request, NationalAssessment $assessment): JsonResponse
    {
        try {
            $rows = Excel::toArray([], $request->file('file'))[0];

            // Skip header row
            $header = array_shift($rows);
            $parsedQuestions = [];
            $errors = [];

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 for 0-index and header

                // Map array values to keys
                $data = array_combine($header, $row);

                // Validate
                if (empty($data['question_text']) || empty($data['type']) || empty($data['points'])) {
                    $errors[] = "Row {$rowNumber}: Missing required fields";

                    continue;
                }

                $type = strtolower(trim($data['type']));
                if (! in_array($type, ['multiple_choice', 'multiple_select', 'true_false'])) {
                    $errors[] = "Row {$rowNumber}: Invalid type '{$data['type']}'";

                    continue;
                }

                // Get choices
                $choices = [];
                $choice1 = trim($data['choice_1_correct_answer'] ?? '');
                $choice2 = trim($data['choice_2'] ?? '');
                $choice3 = trim($data['choice_3'] ?? '');
                $choice4 = trim($data['choice_4'] ?? '');

                if ($type === 'true_false') {
                    $choices = [
                        ['text' => 'True', 'is_correct' => strtolower($choice1) === 'true'],
                        ['text' => 'False', 'is_correct' => strtolower($choice1) !== 'true'],
                    ];
                } else {
                    if (empty($choice1) || empty($choice2)) {
                        $errors[] = "Row {$rowNumber}: At least 2 choices required";

                        continue;
                    }

                    $choices[] = ['text' => $choice1, 'is_correct' => true];
                    $choices[] = ['text' => $choice2, 'is_correct' => false];
                    if (! empty($choice3)) {
                        $choices[] = ['text' => $choice3, 'is_correct' => false];
                    }
                    if (! empty($choice4)) {
                        $choices[] = ['text' => $choice4, 'is_correct' => false];
                    }
                }

                // Get topic info (national exams use topic as string)
                $topicInfo = null;
                if (! empty($data['topic_optional'])) {
                    $topicName = trim($data['topic_optional']);
                    $existingTopic = Topic::where('name', $topicName)->first();

                    $topicInfo = [
                        'name' => $topicName,
                        'exists' => $existingTopic !== null,
                        'will_create' => false, // National exams don't create topics, they just use the name
                    ];
                }

                $parsedQuestions[] = [
                    'row_number' => $rowNumber,
                    'question_text' => trim($data['question_text']),
                    'type' => $type,
                    'points' => (int) $data['points'],
                    'choices' => $choices,
                    'explanation' => ! empty($data['explanation_optional']) ? trim($data['explanation_optional']) : null,
                    'topic' => $topicInfo,
                ];
            }

            return response()->json([
                'success' => true,
                'questions' => $parsedQuestions,
                'errors' => $errors,
                'total_valid' => count($parsedQuestions),
                'total_errors' => count($errors),
            ]);
        } catch (\Exception $e) {
            Log::error('National question preview failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to parse file: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Import questions from Excel file for national assessment.
     */
    public function importQuestions(ImportNationalAssessmentQuestionsRequest $request, NationalAssessment $assessment): RedirectResponse
    {
        try {
            $result = $this->importService->importQuestions(
                $assessment,
                $request->file('file'),
                $request->user(),
            );

            return back()->with($result['status'], $result['message']);
        } catch (\Throwable $exception) {
            return back()->withErrors($this->importService->formatImportFailure($exception));
        }
    }

    /**
     * Download Excel template for bulk question import.
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        return Excel::download(new QuestionsTemplateExport, 'question_import_template.xlsx');
    }

    /**
     * Display draft in-service exams (unpublished).
     */
    public function drafts(Request $request): Response
    {
        $drafts = $this->readService->drafts($request->only(['search', 'sort', 'direction']));

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
        return Inertia::render('assessments/show', $this->readService->showPayload($assessment));
    }

    /**
     * Update the specified national assessment.
     */
    public function update(UpdateNationalAssessmentRequest $request, NationalAssessment $assessment): RedirectResponse
    {
        $this->managementService->update($assessment, $request->validated());

        return back()->with('success', 'Assessment updated successfully');
    }

    /**
     * Remove the specified national assessment.
     */
    public function destroy(NationalAssessment $assessment): RedirectResponse
    {
        if (! $this->managementService->canDelete($assessment)) {
            return back()->withErrors(['error' => 'Cannot delete an exam that has attempts.']);
        }

        $this->managementService->delete($assessment);

        return redirect()->route('inservice-exams.index')->with('success', 'Assessment deleted successfully');
    }

    /**
     * Get activity logs for an assessment.
     */
    public function logs(NationalAssessment $assessment): JsonResponse
    {
        $logs = $this->activityLogService->getLogs($assessment);

        return response()->json([
            'logs' => $logs,
        ]);
    }

}
