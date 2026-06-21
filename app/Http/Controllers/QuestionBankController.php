<?php

namespace App\Http\Controllers;

use App\Models\QuestionBank;
use App\Repositories\Contracts\QuestionBankRepositoryInterface;
use App\Services\ActivityLog\QuestionBankActivityLogService;
use App\Services\QuestionBankImportService;
use App\Services\QuestionBankManagementService;
use App\Services\QuestionBankReadService;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuestionBankController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected QuestionBankActivityLogService $activityLogService,
        protected QuestionBankRepositoryInterface $questionBankRepository,
        protected QuestionBankManagementService $questionBankManagementService,
        protected QuestionBankReadService $questionBankReadService,
        protected QuestionBankImportService $questionBankImportService,
    ) {}
    /**
     * Display question bank listing.
     */
    public function index(Request $request): Response
    {
        $payload = $this->questionBankReadService->indexPayload(
            $request->user(),
            $request->only(['search', 'topic', 'type', 'difficulty', 'approval'])
        );

        return Inertia::render('question-bank/index', [
            'questions' => $payload['questions'],
            'filters' => $request->only(['search', 'topic', 'type', 'difficulty', 'approval']),
        ]);
    }

    /**
     * Return question bank data as JSON (used by selectors).
     */
    public function list(Request $request): JsonResponse
    {
        return response()->json(
            $this->questionBankReadService->listPayload(
                $request->user(),
                $request->only(['search', 'topic', 'type', 'difficulty', 'approval']),
                $request->string('scope')->toString()
            )
        );
    }

    /**
     * Store a new question in the bank.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'topic_id' => ['nullable', 'exists:topics,id'],
            'question_type' => ['required', 'in:multiple_choice,multiple_select,true_false'],
            'question_text' => ['required', 'string'],
            'points' => ['required', 'integer', 'min:1'],
            'explanation' => ['nullable', 'string'],
            'difficulty_level' => ['nullable', 'in:easy,medium,hard'],
            'image' => ['nullable', 'image', 'max:5120', 'mimes:jpeg,jpg,png,gif,webp'],
            'choices' => ['required', 'array', 'min:2'],
            'choices.*.choice_text' => ['required', 'string'],
            'choices.*.is_correct' => ['required', 'boolean'],
        ]);

        $question = $this->questionBankManagementService->create(
            $user,
            $validated,
            $request->file('image')
        );

        // Log question creation
        $this->activityLogService->logQuestionCreated($question);

        return redirect()->route('question-bank.index')
            ->with('success', 'Question added to bank successfully!');
    }

    /**
     * Update a question in the bank.
     */
    public function update(Request $request, QuestionBank $question): RedirectResponse
    {
        $user = $request->user();
        if (! $this->questionBankManagementService->canAccess($user, $question)) {
            abort(403, 'You do not have access to this question.');
        }

        // Load choices relationship before updating
        $question->load('choices');

        $validated = $request->validate([
            'topic_id' => ['nullable', 'exists:topics,id'],
            'question_type' => ['required', 'in:multiple_choice,multiple_select,true_false'],
            'question_text' => ['required', 'string'],
            'points' => ['required', 'integer', 'min:1'],
            'explanation' => ['nullable', 'string'],
            'difficulty_level' => ['nullable', 'in:easy,medium,hard'],
            'image' => ['nullable', 'image', 'max:5120', 'mimes:jpeg,jpg,png,gif,webp'],
            'choices' => ['required', 'array', 'min:2'],
            'choices.*.choice_text' => ['required', 'string'],
            'choices.*.is_correct' => ['required', 'boolean'],
        ], [
            'choices.required' => 'At least two choices are required',
            'choices.min' => 'At least two choices are required',
        ]);

        // Validate that multiple_choice has exactly one correct answer
        if ($validated['question_type'] === 'multiple_choice') {
            $correctCount = collect($validated['choices'])->where('is_correct', true)->count();
            if ($correctCount !== 1) {
                return back()->withErrors([
                    'choices' => 'Multiple choice questions must have exactly one correct answer.',
                ]);
            }
        }

        // Capture old values before updating
        $oldTopicId = $question->topic_id;
        $oldQuestionType = $question->question_type;
        $oldQuestionText = $question->question_text;
        $oldPoints = $question->points;
        $oldExplanation = $question->explanation;
        $oldDifficultyLevel = $question->difficulty_level;
        $imageChanged = $request->hasFile('image');

        // Capture old choices before they're deleted
        $oldChoices = $question->choices()->orderBy('order')->get()->map(function ($choice) {
            return [
                'choice_text' => $choice->choice_text,
                'is_correct' => $choice->is_correct,
                'order' => $choice->order,
            ];
        })->toArray();

        $updateResult = [];
        $this->withoutActivityLogging(function () use ($question, $validated, $request, &$updateResult) {
            $updateResult = $this->questionBankManagementService->update($question, $validated, $request->file('image'));
        });

        $this->questionBankChoiceRepository->replaceForQuestion($question, $validated['choices']);

        // Build consolidated log entry with all changes using service
        $logData = $this->activityLogService->buildUpdateLogData(
            $question,
            $updateResult['attributes'],
            $oldTopicId,
            $oldQuestionType,
            $oldQuestionText,
            $oldPoints,
            $oldExplanation,
            $oldDifficultyLevel,
            $updateResult['imageChanged'],
            $oldChoices
        );

        // Log all changes in a single entry
        if ($logData['hasChanges']) {
            $this->activityLogService->logQuestionUpdated(
                $question,
                $logData['attributes'],
                $logData['oldValues']
            );
        }

        return back()->with('success', 'Question updated successfully!');
    }

    /**
     * Delete a question from the bank.
     */
    public function destroy(Request $request, QuestionBank $question): RedirectResponse
    {
        $user = $request->user();
        if (! $this->questionBankManagementService->canAccess($user, $question)) {
            abort(403, 'You do not have access to this question.');
        }

        // Check if question is used in any exams
        if ($this->questionBankManagementService->isUsedInAssessments($question)) {
            return back()->with('error', 'Cannot delete question that is used in exams. Remove from exams first.');
        }

        // Log deletion before deleting
        $this->activityLogService->logQuestionDeleted($question);

        $this->questionBankManagementService->delete($question);

        return back()->with('success', 'Question deleted successfully!');
    }

    /**
     * Approve a question.
     */
    public function approve(Request $request, QuestionBank $question): RedirectResponse
    {
        $user = $request->user();
        if (! $this->questionBankManagementService->canAccess($user, $question)) {
            abort(403, 'You do not have access to this question.');
        }

        $this->questionBankManagementService->approve($question, $user->id);

        // Log question approval
        $this->activityLogService->logQuestionApproved($question);

        return back()->with('success', 'Question approved successfully!');
    }

    /**
     * Get statistics for question bank.
     */
    public function statistics(Request $request): Response
    {
        $statistics = $this->questionBankReadService->statisticsPayload($request->user());

        return Inertia::render('question-bank/statistics', [
            'totalQuestions' => $statistics['totalQuestions'],
            'approvedQuestions' => $statistics['approvedQuestions'],
            'byType' => $statistics['byType'],
            'topPerforming' => $statistics['topPerforming'],
            'needsReview' => $statistics['needsReview'],
        ]);
    }

    /**
     * Preview questions from Excel import.
     */
    public function previewImport(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        try {
            return response()->json(
                $this->questionBankImportService->preview($request->file('file'))
            );
        } catch (\Exception $e) {
            return response()->json([
                'questions' => [],
                'errors' => [['row' => 0, 'error' => $e->getMessage()]],
                'total_valid' => 0,
                'total_errors' => 1,
            ], 500);
        }
    }

    /**
     * Import questions from Excel to Question Bank.
     */
    public function import(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        try {
            $result = $this->questionBankImportService->import($user, $request->file('file'));
            $successCount = $result['successCount'];
            $errors = $result['errors'];

            if (! empty($errors)) {
                $errorMessage = "Imported {$successCount} questions with " . count($errors) . ' errors: ';
                $errorMessage .= implode('; ', array_slice($errors, 0, 3));

                if (count($errors) > 3) {
                    $errorMessage .= '... and ' . (count($errors) - 3) . ' more errors.';
                }

                return back()->with('warning', $errorMessage);
            }

            return back()->with('success', "Successfully imported {$successCount} questions to Question Bank!");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Question Bank import failed', ['error' => $e->getMessage()]);

            return back()->withErrors(['file' => 'Import failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Get activity logs for a question.
     */
    public function logs(QuestionBank $question): JsonResponse
    {
        $logs = $this->activityLogService->getLogs($question);

        return response()->json([
            'logs' => $logs,
        ]);
    }
}
