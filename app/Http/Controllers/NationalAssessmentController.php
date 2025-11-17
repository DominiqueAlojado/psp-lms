<?php

namespace App\Http\Controllers;

use App\Exports\QuestionsTemplateExport;
use App\Imports\NationalQuestionsImport;
use App\Models\National\NationalAssessment;
use App\Models\National\NationalQuestion;
use App\Models\National\NationalQuestionChoice;
use App\Models\QuestionBank;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
     * Display active (published) in-service exams.
     */
    public function active(Request $request): Response
    {
        $query = NationalAssessment::query()
            ->where('is_published', true) // Only published exams
            ->with(['questions', 'creator:id,name'])
            ->withCount('questions')
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
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

        \Log::info('Active exams query result', [
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
                'questions' => $assessment->questions->map(function ($q) {
                    // Find topic_id from topic name if topic exists
                    $topicId = null;
                    if ($q->topic) {
                        $topic = Topic::where('name', $q->topic)->first();
                        $topicId = $topic?->id;
                    }

                    return [
                        'id' => $q->id,
                        'question_type' => $q->question_type,
                        'question_text' => $q->question_text,
                        'points' => $q->points,
                        'topic' => $q->topic,
                        'topic_id' => $topicId,
                        'order' => $q->order,
                        'image_path' => $q->image_path,
                        'image_url' => $q->image_path ? Storage::url($q->image_path) : null,
                        'choices' => $q->choices->map(fn($c) => [
                            'id' => $c->id,
                            'choice_text' => $c->choice_text,
                            'is_correct' => $c->is_correct,
                            'order' => $c->order,
                        ]),
                    ];
                }),
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
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
            'choices' => ['nullable', 'array'],
            'choices.*.id' => ['nullable', 'integer', 'exists:national_question_choices,id'],
            'choices.*.choice_text' => ['required_with:choices', 'string'],
            'choices.*.is_correct' => ['required_with:choices', 'boolean'],
            'answer' => ['nullable', 'boolean'],
            'image' => ['nullable', 'string'], // base64
        ]);

        // Check if this is a new question (no ID provided or ID is 0/null) or existing (ID provided)
        // Handle cases where frontend might send id: 0, id: null, id: undefined, or no id field
        $hasValidId = !empty($validated['id']) && $validated['id'] > 0;
        $isNewQuestion = !$hasValidId;

        \Log::info('Saving question', [
            'is_new' => $isNewQuestion,
            'has_id' => !empty($validated['id']),
            'question_id' => $validated['id'] ?? 'none',
            'question_id_type' => gettype($validated['id'] ?? null),
            'assessment_id' => $assessment->id,
            'question_text_preview' => substr($validated['question_text'] ?? '', 0, 50),
        ]);

        $question = null;
        if (! $isNewQuestion) {
            // Look for existing question by ID
            $question = NationalQuestion::query()
                ->where('assessment_id', $assessment->id)
                ->where('id', $validated['id'])
                ->first();
        }

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

        // Handle topic: prefer topic_id (convert to name), fallback to topic string
        if (! empty($validated['topic_id'])) {
            $topic = Topic::find($validated['topic_id']);
            $question->topic = $topic?->name;
        } else {
            $question->topic = $validated['topic'] ?? null;
        }

        // Handle base64 image upload
        $imagePath = null;
        if (! empty($validated['image']) && str_starts_with($validated['image'], 'data:image')) {
            $data = explode(',', $validated['image'], 2)[1] ?? null;
            if ($data) {
                $binary = base64_decode($data);
                $path = 'national-questions/' . uniqid() . '_' . time() . '.png';
                Storage::disk('public')->put($path, $binary);
                $question->image_path = $path;
                $imagePath = $path;
            }
        }

        $question->save();

        // Sync choices and collect choices data for question bank
        $choicesData = [];
        if (in_array($question->question_type, ['multiple_choice', 'multiple_select'])) {
            $choiceOrder = 1;
            $question->choices()->delete();
            foreach ($validated['choices'] ?? [] as $choice) {
                $question->choices()->create([
                    'choice_text' => $choice['choice_text'],
                    'is_correct' => (bool) $choice['is_correct'],
                    'order' => $choiceOrder++,
                ]);
                $choicesData[] = [
                    'choice_text' => $choice['choice_text'],
                    'is_correct' => (bool) $choice['is_correct'],
                ];
            }
        } elseif ($question->question_type === 'true_false') {
            $question->choices()->delete();
            $answer = (bool) ($validated['answer'] ?? true);
            $question->choices()->createMany([
                ['choice_text' => 'True', 'is_correct' => $answer === true, 'order' => 1],
                ['choice_text' => 'False', 'is_correct' => $answer === false, 'order' => 2],
            ]);
            $choicesData = [
                ['choice_text' => 'True', 'is_correct' => $answer === true],
                ['choice_text' => 'False', 'is_correct' => $answer === false],
            ];
        }

        // Save to question bank if this is a new question OR if it doesn't exist in question bank yet
        $topicId = $validated['topic_id'] ?? null;
        $topicName = $question->topic; // Already converted from topic_id if needed

        // Check if this question already exists in question bank (by text and owner)
        $existsInBank = QuestionBank::where('question_text', $question->question_text)
            ->where('owner_type', 'national')
            ->where('created_by', $request->user()->id)
            ->exists();

        if ($isNewQuestion || !$existsInBank) {
            try {
                $this->saveToQuestionBank($question, $choicesData, $imagePath, $topicId, $topicName, $request->user());
                \Log::info('Question saved to question bank', [
                    'question_id' => $question->id,
                    'assessment_id' => $assessment->id,
                    'is_new' => $isNewQuestion,
                    'exists_in_bank' => $existsInBank,
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to save question to question bank', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'question_id' => $question->id,
                ]);
            }
        } else {
            \Log::info('Question not saved to question bank - already exists', [
                'question_id' => $question->id,
                'has_id' => !empty($validated['id']),
                'exists_in_bank' => $existsInBank,
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
     * Duplicate an existing national assessment and its questions.
     */
    public function duplicate(Request $request, NationalAssessment $assessment): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $duplicate = $assessment->replicate();
        $duplicate->title = $validated['title'] ?? $assessment->title . ' (Copy)';
        $duplicate->is_published = false;
        $duplicate->scheduled_date = null;
        $duplicate->results_release_date = null;
        $duplicate->created_by = $request->user()->id;
        $duplicate->total_points = 0;
        $duplicate->save();

        // Duplicate questions
        foreach ($assessment->questions as $question) {
            $newQuestion = $question->replicate();
            $newQuestion->assessment_id = $duplicate->id;
            $newQuestion->save();

            // Duplicate choices
            foreach ($question->choices as $choice) {
                $newChoice = $choice->replicate();
                $newChoice->question_id = $newQuestion->id;
                $newChoice->save();
            }
        }

        // Recompute total points
        $totalPoints = $duplicate->questions()->sum('points');
        $duplicate->update(['total_points' => $totalPoints]);

        return redirect()
            ->route('inservice-exams.edit', $duplicate)
            ->with('success', 'Exam duplicated successfully. You can now make changes.');
    }

    /**
     * Preview questions from uploaded Excel file for national assessment.
     */
    public function previewQuestions(Request $request, NationalAssessment $assessment): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

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
            \Log::error('National question preview failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to parse file: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Import questions from Excel file for national assessment.
     */
    public function importQuestions(Request $request, NationalAssessment $assessment): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'], // 5MB max
        ]);

        try {
            $import = new NationalQuestionsImport($assessment->id, $request->user());
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

            // Recompute total points after import
            $total = $assessment->questions()->sum('points');
            $assessment->update(['total_points' => $total]);

            return back()->with('success', "Successfully imported {$successCount} questions!");
        } catch (\Exception $e) {
            \Log::error('National question import failed', ['error' => $e->getMessage()]);

            return back()->withErrors(['file' => 'Import failed: ' . $e->getMessage()]);
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
            'exam_year' => ['sometimes', 'integer', 'min:2000', 'max:3000'],
            'exam_period' => ['sometimes', 'string', 'max:100'],
            'category' => ['sometimes', 'nullable', 'in:anatomic-pathology-theoretical,anatomic-pathology-projection,clinical-pathology-theoretical,clinical-pathology-projection'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'passing_score' => ['required', 'integer', 'min:0'],
            'randomize_questions' => ['boolean'],
            'randomize_choices' => ['boolean'],
            'show_results_immediately' => ['boolean'],
            'allow_review' => ['boolean'],
            'is_published' => ['boolean'],
            'national_ranking_enabled' => ['sometimes', 'boolean'],
            'institution_comparison_enabled' => ['sometimes', 'boolean'],
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

        return redirect()->route('inservice-exams.index')->with('success', 'Assessment deleted successfully');
    }

    /**
     * Save a question created in a national exam to the question bank.
     */
    private function saveToQuestionBank(
        NationalQuestion $question,
        array $choicesData,
        ?string $imagePath,
        ?int $topicId,
        ?string $topicName,
        $user
    ): void {
        try {
            // Use topic_id if provided, otherwise try to find by name
            if (! $topicId && $topicName) {
                $topic = Topic::where('name', $topicName)->first();
                $topicId = $topic?->id;
            }

            // Create question in question bank
            $bankQuestion = QuestionBank::create([
                'organization_id' => null, // National questions don't belong to a specific organization
                'owner_type' => 'national',
                'topic_id' => $topicId,
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
                'scope' => 'national',
                'institution_id' => null,
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the question creation
            \Log::error('Failed to save question to question bank', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // Re-throw so outer try-catch can log it
        }
    }
}
