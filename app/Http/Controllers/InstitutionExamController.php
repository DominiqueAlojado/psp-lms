<?php

namespace App\Http\Controllers;

use App\Exports\QuestionsTemplateExport;
use App\Imports\QuestionsImport;
use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionQuestion;
use App\Repositories\Contracts\InstitutionAssessmentRepositoryInterface;
use App\Repositories\Contracts\InstitutionQuestionChoiceRepositoryInterface;
use App\Repositories\Contracts\InstitutionQuestionRepositoryInterface;
use App\Repositories\Contracts\QuestionBankChoiceRepositoryInterface;
use App\Repositories\Contracts\QuestionBankRepositoryInterface;
use App\Repositories\Contracts\QuestionBankStatisticRepositoryInterface;
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
        private readonly QuestionBankRepositoryInterface $questionBankRepository,
        private readonly QuestionBankChoiceRepositoryInterface $questionBankChoiceRepository,
        private readonly QuestionBankStatisticRepositoryInterface $questionBankStatisticRepository,
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

        DB::transaction(function () use ($validated, $assessment) {
            $existingQuestionIds = [];
            $totalPoints = 0;

            foreach ($validated['questions'] as $q) {
                $imagePath = $this->storeBase64QuestionImage($q['image'] ?? null);
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

                $question = null;
                if (! empty($q['id'])) {
                    $question = $this->questionRepository->findForAssessment($assessment, (int) $q['id']);
                    if ($question) {
                        $this->questionRepository->update($question, $questionData);
                        $this->questionChoiceRepository->deleteForQuestion($question);
                    }
                }

                if (! $question) {
                    $question = $this->questionRepository->createForAssessment($assessment, $questionData);
                }

                $existingQuestionIds[] = $question->id;

                $this->syncQuestionChoices($question, $q);

                $totalPoints += $q['points'];
            }

            $this->questionRepository->deleteMissingForAssessment($assessment, $existingQuestionIds);
            $this->assessmentRepository->update($assessment, ['total_points' => $totalPoints]);
        });

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

        $imagePath = $this->storeBase64QuestionImage($validated['image'] ?? null);

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

        // Check if this is a new question (no ID provided or ID is 0/null) or existing (ID provided)
        // Handle cases where frontend might send id: 0, id: null, id: undefined, or no id field
        $hasValidId = !empty($validated['id']) && $validated['id'] > 0;
        $isNewQuestion = !$hasValidId;

        Log::info('Saving institution question', [
            'is_new' => $isNewQuestion,
            'has_id' => !empty($validated['id']),
            'question_id' => $validated['id'] ?? 'none',
            'question_id_type' => gettype($validated['id'] ?? null),
            'assessment_id' => $assessment->id,
            'question_text_preview' => substr($validated['question_text'] ?? '', 0, 50),
        ]);

        // Update existing question or create new one
        $question = DB::transaction(function () use ($assessment, $validated, $questionData, $isNewQuestion) {
            $question = null;

            if (! $isNewQuestion) {
                $question = $this->questionRepository->findForAssessment($assessment, (int) $validated['id']);
                if ($question) {
                    $this->questionRepository->update($question, $questionData);
                    $this->questionChoiceRepository->deleteForQuestion($question);
                }
            }

            if (! $question) {
                $question = $this->questionRepository->createForAssessment($assessment, $questionData);
            }

            $this->syncQuestionChoices($question, $validated);

            $this->assessmentRepository->update($assessment, [
                'total_points' => $this->assessmentRepository->sumQuestionPoints($assessment),
            ]);

            return $question;
        });

        $choicesData = $this->buildChoicesData($validated);

        // Save to question bank if this is a new question OR if it doesn't exist in question bank yet
        // Check if this question already exists in question bank (by text, owner, and organization)
        $existsInBank = $this->questionBankRepository->existsForInstitutionCreator(
            $question->question_text,
            $assessment->organization_id,
            $request->user()->id
        );

        if ($isNewQuestion || !$existsInBank) {
            try {
                $this->saveToQuestionBank($question, $assessment, $choicesData, $imagePath, $request->user());
                Log::info('Institution question saved to question bank', [
                    'question_id' => $question->id,
                    'assessment_id' => $assessment->id,
                    'is_new' => $isNewQuestion,
                    'exists_in_bank' => $existsInBank,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to save institution question to question bank', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'question_id' => $question->id,
                ]);
            }
        } else {
            Log::info('Institution question not saved to question bank - already exists', [
                'question_id' => $question->id,
                'has_id' => !empty($validated['id']),
                'exists_in_bank' => $existsInBank,
            ]);
        }

        // Recalculate total points
        $this->assessmentRepository->update($assessment, [
            'total_points' => $this->assessmentRepository->sumQuestionPoints($assessment),
        ]);

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

        DB::transaction(function () use ($assessment, $question) {
            $this->questionRepository->delete($question);
            $this->assessmentRepository->update($assessment, [
                'total_points' => $this->assessmentRepository->sumQuestionPoints($assessment),
            ]);
        });

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

        $isNationalOrgAssessment = $assessment->organization?->type === 'national';

        $bankQuestions = $isNationalOrgAssessment
            ? $this->questionBankRepository->findByIdsForOwnerType($request->question_ids, 'national')
            : $this->questionBankRepository->findByIdsForOrganization(
                $request->question_ids,
                $assessment->organization_id
            );

        if ($bankQuestions->isEmpty()) {
            return back()->with('error', 'No valid questions found.');
        }

        $addedCount = DB::transaction(function () use ($assessment, $bankQuestions) {
            $addedCount = 0;

            foreach ($bankQuestions as $bankQuestion) {
                $question = $this->questionRepository->createForAssessment($assessment, [
                    'topic_id' => $bankQuestion->topic_id,
                    'question_type' => $bankQuestion->question_type,
                    'question_text' => $bankQuestion->question_text,
                    'points' => $bankQuestion->points,
                    'explanation' => $bankQuestion->explanation,
                    'image_path' => $bankQuestion->image_path,
                ]);

                $this->questionChoiceRepository->createMany(
                    $question,
                    $bankQuestion->choices->map(fn($bankChoice) => [
                        'choice_text' => $bankChoice->choice_text,
                        'is_correct' => $bankChoice->is_correct,
                        'order' => $bankChoice->order,
                    ])->all()
                );

                $this->questionBankRepository->incrementUsage($bankQuestion);
                $addedCount++;
            }

            $this->assessmentRepository->update($assessment, [
                'total_points' => $this->assessmentRepository->sumQuestionPoints($assessment),
            ]);

            return $addedCount;
        });

        return back()->with('success', "Successfully added {$addedCount} questions from question bank!");
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
            Log::info('Attempting to save institution question to question bank', [
                'question_text' => substr($question->question_text, 0, 50),
                'topic_id' => $question->topic_id,
                'organization_id' => $assessment->organization_id,
                'user_id' => $user->id,
                'choices_count' => count($choicesData),
            ]);

            // Create question in question bank
            $bankQuestion = $this->questionBankRepository->create([
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

            Log::info('Institution question bank entry created', ['bank_question_id' => $bankQuestion->id]);

            $this->questionBankChoiceRepository->createMany($bankQuestion, $choicesData);

            Log::info('Choices created in institution question bank', ['count' => count($choicesData)]);

            $this->questionBankStatisticRepository->initializeForQuestion(
                $bankQuestion,
                'institution',
                $assessment->organization_id
            );

            Log::info('Statistics initialized for institution question bank entry');
        } catch (\Exception $e) {
            // Log error but don't fail the question creation
            Log::error('Failed to save institution question to question bank', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // Re-throw so outer try-catch can log it
        }
    }

    private function storeBase64QuestionImage(?string $image): ?string
    {
        if (empty($image)) {
            return null;
        }

        try {
            $imageData = $image;
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                $imageData = substr($imageData, strpos($imageData, ',') + 1);
                $type = strtolower($type[1]);
                $imageData = base64_decode($imageData);

                if ($imageData !== false) {
                    $filename = 'question_' . uniqid() . '.' . $type;
                    $path = 'question-images/' . $filename;
                    Storage::disk('public')->put($path, $imageData);

                    return $path;
                }
            }
        } catch (\Exception $e) {
            Log::error('Error uploading question image: ' . $e->getMessage());
        }

        return null;
    }

    private function syncQuestionChoices(InstitutionQuestion $question, array $payload): void
    {
        if (in_array($payload['question_type'], ['multiple_choice', 'multiple_select'])) {
            $this->questionChoiceRepository->createMany(
                $question,
                collect($payload['choices'] ?? [])->map(fn($choice, $index) => [
                    'choice_text' => $choice['choice_text'],
                    'is_correct' => (bool) ($choice['is_correct'] ?? false),
                    'order' => $index,
                ])->all()
            );
        }

        if ($payload['question_type'] === 'true_false') {
            $answer = filter_var($payload['answer'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $this->questionChoiceRepository->createMany($question, [
                ['choice_text' => 'True', 'is_correct' => $answer === true, 'order' => 0],
                ['choice_text' => 'False', 'is_correct' => $answer === false, 'order' => 1],
            ]);
        }
    }

    private function buildChoicesData(array $payload): array
    {
        if (in_array($payload['question_type'], ['multiple_choice', 'multiple_select'])) {
            return collect($payload['choices'] ?? [])->map(fn($choice) => [
                'choice_text' => $choice['choice_text'],
                'is_correct' => (bool) ($choice['is_correct'] ?? false),
            ])->all();
        }

        if ($payload['question_type'] === 'true_false') {
            $answer = filter_var($payload['answer'] ?? false, FILTER_VALIDATE_BOOLEAN);

            return [
                ['choice_text' => 'True', 'is_correct' => $answer === true],
                ['choice_text' => 'False', 'is_correct' => $answer === false],
            ];
        }

        return [];
    }
}
