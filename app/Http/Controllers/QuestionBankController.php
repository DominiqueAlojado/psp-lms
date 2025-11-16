<?php

namespace App\Http\Controllers;

use App\Models\QuestionBank;
use App\Models\QuestionBankChoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class QuestionBankController extends Controller
{
    /**
     * Display question bank listing.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $organizationId = $user->currentOrganization?->id;

        $query = QuestionBank::query()
            ->with(['topic:id,name', 'creator:id,name', 'statistics', 'choices'])
            ->forOrganization($organizationId);

        // Search
        if ($request->filled('search')) {
            $query->where('question_text', 'like', '%' . $request->search . '%');
        }

        // Filter by topic
        if ($request->filled('topic')) {
            $query->where('topic_id', $request->topic);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('question_type', $request->type);
        }

        // Filter by difficulty
        if ($request->filled('difficulty')) {
            if ($request->difficulty === 'manual') {
                $query->whereNotNull('difficulty_level');
            } else {
                $query->whereHas('statistics', function ($q) use ($request) {
                    $q->where('computed_difficulty', $request->difficulty);
                });
            }
        }

        // Filter by approval status
        if ($request->filled('approval')) {
            if ($request->approval === 'approved') {
                $query->where('is_approved', true);
            } elseif ($request->approval === 'pending') {
                $query->where('is_approved', false);
            }
        }

        $questions = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('question-bank/index', [
            'questions' => $questions,
            'filters' => $request->only(['search', 'topic', 'type', 'difficulty', 'approval']),
        ]);
    }

    /**
     * Return question bank data as JSON (used by selectors).
     */
    public function list(Request $request): JsonResponse
    {
        $user = $request->user();
        $organizationId = $user->currentOrganization?->id;

        $query = QuestionBank::query()
            ->with(['topic:id,name', 'statistics', 'choices'])
            ->forOrganization($organizationId);

        if ($request->filled('search')) {
            $query->where('question_text', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('topic')) {
            $query->where('topic_id', $request->topic);
        }

        if ($request->filled('type')) {
            $query->where('question_type', $request->type);
        }

        if ($request->filled('difficulty')) {
            if ($request->difficulty === 'manual') {
                $query->whereNotNull('difficulty_level');
            } else {
                $query->whereHas('statistics', function ($q) use ($request) {
                    $q->where('computed_difficulty', $request->difficulty);
                });
            }
        }

        if ($request->filled('approval')) {
            if ($request->approval === 'approved') {
                $query->where('is_approved', true);
            } elseif ($request->approval === 'pending') {
                $query->where('is_approved', false);
            }
        }

        $questions = $query->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $questions,
        ]);
    }

    /**
     * Store a new question in the bank.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $organizationId = $user->currentOrganization?->id;

        // Determine scope based on URL param 'org'
        // If org=in-service-exams => national, else institution
        $orgParam = (string) $request->query('org', '');
        $isNational = $orgParam === 'in-service-exams';
        $scope = $isNational ? 'national' : 'institution';

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

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $imagePath = $file->storeAs('question-images', $fileName, 'public');
        }

        // Create question
        $question = QuestionBank::create([
            ...$validated,
            'organization_id' => $organizationId,
            'created_by' => $user->id,
            'image_path' => $imagePath,
        ]);

        // Initialize statistics with proper scope
        $question->statistics()->create([
            'question_id' => $question->id,
            'scope' => $scope,
            'institution_id' => $isNational ? null : $organizationId,
        ]);

        // Create choices
        foreach ($validated['choices'] as $index => $choice) {
            QuestionBankChoice::create([
                'question_id' => $question->id,
                'choice_text' => $choice['choice_text'],
                'is_correct' => $choice['is_correct'],
                'order' => $index,
            ]);
        }

        return redirect()->route('question-bank.index')
            ->with('success', 'Question added to bank successfully!');
    }

    /**
     * Update a question in the bank.
     */
    public function update(Request $request, QuestionBank $question): RedirectResponse
    {
        $user = $request->user();
        $organizationId = $user->currentOrganization?->id;

        // Check access
        if ($question->organization_id !== $organizationId) {
            abort(403, 'You do not have access to this question.');
        }

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

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($question->image_path && Storage::disk('public')->exists($question->image_path)) {
                Storage::disk('public')->delete($question->image_path);
            }

            $file = $request->file('image');
            $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $validated['image_path'] = $file->storeAs('question-images', $fileName, 'public');
        }

        // Update question
        $question->update($validated);

        // Update choices - delete old ones and create new
        $question->choices()->delete();
        foreach ($validated['choices'] as $index => $choice) {
            QuestionBankChoice::create([
                'question_id' => $question->id,
                'choice_text' => $choice['choice_text'],
                'is_correct' => $choice['is_correct'],
                'order' => $index,
            ]);
        }

        return back()->with('success', 'Question updated successfully!');
    }

    /**
     * Delete a question from the bank.
     */
    public function destroy(Request $request, QuestionBank $question): RedirectResponse
    {
        $user = $request->user();
        $organizationId = $user->currentOrganization?->id;

        // Check access
        if ($question->organization_id !== $organizationId) {
            abort(403, 'You do not have access to this question.');
        }

        // Check if question is used in any exams
        if ($question->assessments()->count() > 0) {
            return back()->with('error', 'Cannot delete question that is used in exams. Remove from exams first.');
        }

        $question->delete();

        return back()->with('success', 'Question deleted successfully!');
    }

    /**
     * Approve a question.
     */
    public function approve(Request $request, QuestionBank $question): RedirectResponse
    {
        $user = $request->user();
        $organizationId = $user->currentOrganization?->id;

        // Check access
        if ($question->organization_id !== $organizationId) {
            abort(403, 'You do not have access to this question.');
        }

        $question->update([
            'is_approved' => true,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Question approved successfully!');
    }

    /**
     * Get statistics for question bank.
     */
    public function statistics(Request $request): Response
    {
        $user = $request->user();
        $organizationId = $user->currentOrganization?->id;

        $totalQuestions = QuestionBank::forOrganization($organizationId)->count();
        $approvedQuestions = QuestionBank::forOrganization($organizationId)->approved()->count();

        $byType = QuestionBank::forOrganization($organizationId)
            ->selectRaw('question_type, COUNT(*) as count')
            ->groupBy('question_type')
            ->get();

        $topPerforming = QuestionBank::forOrganization($organizationId)
            ->with(['topic', 'statistics'])
            ->whereHas('statistics', function ($q) {
                $q->where('times_answered', '>', 10);
            })
            ->get()
            ->sortByDesc('statistics.success_rate')
            ->take(10);

        $needsReview = QuestionBank::forOrganization($organizationId)
            ->with(['topic', 'statistics'])
            ->whereHas('statistics', function ($q) {
                $q->where('times_answered', '>', 10)
                    ->where('success_rate', '<', 40);
            })
            ->get();

        return Inertia::render('question-bank/statistics', [
            'totalQuestions' => $totalQuestions,
            'approvedQuestions' => $approvedQuestions,
            'byType' => $byType,
            'topPerforming' => $topPerforming,
            'needsReview' => $needsReview,
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
            $file = $request->file('file');
            $rows = Excel::toArray([], $file)[0];

            if (empty($rows)) {
                return response()->json([
                    'questions' => [],
                    'errors' => [['row' => 0, 'error' => 'File is empty']],
                    'total_valid' => 0,
                    'total_errors' => 1,
                ]);
            }

            $headers = array_shift($rows);
            $questions = [];
            $errors = [];

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                if (empty(array_filter($row))) {
                    continue;
                }

                $rowData = array_combine($headers, $row);

                try {
                    $question = [
                        'question_text' => $rowData['question_text'] ?? '',
                        'type' => $rowData['type'] ?? '',
                        'points' => (int) ($rowData['points'] ?? 1),
                        'topic' => $rowData['topic'] ?? '',
                        'explanation' => $rowData['explanation'] ?? '',
                        'choices' => [],
                    ];

                    for ($i = 1; $i <= 6; $i++) {
                        $choiceText = $rowData["choice_{$i}"] ?? '';
                        $isCorrect = isset($rowData["choice_{$i}_correct_answer"]) &&
                            in_array(strtolower($rowData["choice_{$i}_correct_answer"]), ['yes', '1', 'true']);

                        if (! empty($choiceText)) {
                            $question['choices'][] = [
                                'text' => $choiceText,
                                'is_correct' => $isCorrect,
                            ];
                        }
                    }

                    if (empty($question['question_text'])) {
                        throw new \Exception('Question text is required');
                    }

                    if (empty($question['choices'])) {
                        throw new \Exception('At least one choice is required');
                    }

                    $questions[] = $question;
                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'questions' => $questions,
                'errors' => $errors,
                'total_valid' => count($questions),
                'total_errors' => count($errors),
            ]);
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
        $organizationId = $user->currentOrganization?->id;

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        try {
            $file = $request->file('file');
            $rows = Excel::toArray([], $file)[0];

            if (empty($rows)) {
                return back()->with('error', 'File is empty');
            }

            $headers = array_shift($rows);
            $successCount = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                if (empty(array_filter($row))) {
                    continue;
                }

                $rowData = array_combine($headers, $row);

                try {
                    // Find or create topic
                    $topicId = null;
                    if (! empty($rowData['topic'])) {
                        $topicSlug = Str::slug($rowData['topic']);
                        $topic = \App\Models\Topic::where('slug', $topicSlug)
                            ->where(function ($query) use ($organizationId) {
                                $query->where('organization_id', $organizationId)
                                    ->orWhereNull('organization_id');
                            })
                            ->first();

                        if (! $topic) {
                            $topic = \App\Models\Topic::create([
                                'name' => $rowData['topic'],
                                'slug' => $topicSlug . '-' . uniqid(),
                                'organization_id' => $organizationId,
                            ]);
                        }

                        $topicId = $topic->id;
                    }

                    // Create question in bank
                    $question = QuestionBank::create([
                        'organization_id' => $organizationId,
                        'created_by' => $user->id,
                        'topic_id' => $topicId,
                        'question_type' => $rowData['type'] ?? 'multiple_choice',
                        'question_text' => $rowData['question_text'] ?? '',
                        'points' => (int) ($rowData['points'] ?? 1),
                        'explanation' => $rowData['explanation'] ?? null,
                        'difficulty_level' => 'medium',
                    ]);

                    // Create choices
                    for ($i = 1; $i <= 6; $i++) {
                        $choiceText = $rowData["choice_{$i}"] ?? '';
                        $isCorrect = isset($rowData["choice_{$i}_correct_answer"]) &&
                            in_array(strtolower($rowData["choice_{$i}_correct_answer"]), ['yes', '1', 'true']);

                        if (! empty($choiceText)) {
                            QuestionBankChoice::create([
                                'question_id' => $question->id,
                                'choice_text' => $choiceText,
                                'is_correct' => $isCorrect,
                                'order' => $i - 1,
                            ]);
                        }
                    }

                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                }
            }

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
            \Log::error('Question Bank import failed', ['error' => $e->getMessage()]);

            return back()->withErrors(['file' => 'Import failed: ' . $e->getMessage()]);
        }
    }
}
