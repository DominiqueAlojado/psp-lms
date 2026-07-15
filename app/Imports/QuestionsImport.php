<?php

namespace App\Imports;

use App\Models\Institution\InstitutionQuestion;
use App\Models\Institution\InstitutionQuestionChoice;
use App\Models\QuestionBank;
use App\Models\Topic;
use App\Support\InstitutionQuestionImportRowParser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class QuestionsImport implements ToCollection, WithHeadingRow
{
    protected int $assessmentId;

    protected int $organizationId;

    protected $user;

    protected array $errors = [];

    protected int $successCount = 0;

    private readonly InstitutionQuestionImportRowParser $rowParser;

    public function __construct(int $assessmentId, int $organizationId, $user = null)
    {
        $this->assessmentId = $assessmentId;
        $this->organizationId = $organizationId;
        $this->user = $user;
        $this->rowParser = app(InstitutionQuestionImportRowParser::class);
    }

    public function collection(Collection $rows): void
    {
        DB::beginTransaction();

        try {
            // Get the current max order for questions
            $maxOrder = InstitutionQuestion::where('assessment_id', $this->assessmentId)
                ->max('order') ?? 0;

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 because: +1 for 0-index, +1 for header row

                try {
                    $parsedRow = $this->rowParser->parse($row->toArray(), $rowNumber);
                    $type = $parsedRow['type'];

                    // Find or create topic if provided
                    $topicId = null;
                    if (! empty($row['topic_optional'])) {
                        $topicName = trim($row['topic_optional']);
                        $slug = Str::slug($topicName);

                        // Try to find existing topic (global or organization-specific)
                        $topic = Topic::where('slug', $slug)
                            ->where(function ($query) {
                                $query->where('organization_id', $this->organizationId)
                                    ->orWhere('is_global', true);
                            })
                            ->first();

                        // If not found, create organization-specific topic
                        if (! $topic) {
                            $topic = Topic::create([
                                'name' => $topicName,
                                'organization_id' => $this->organizationId,
                                'slug' => $slug.'-'.uniqid(), // Add unique suffix to avoid conflicts
                                'is_global' => false,
                            ]);
                        }

                        $topicId = $topic->id;
                    }

                    // Create the question
                    $maxOrder++;
                    $question = InstitutionQuestion::create([
                        'assessment_id' => $this->assessmentId,
                        'topic_id' => $topicId,
                        'question_type' => $type,
                        'question_text' => $parsedRow['question_text'],
                        'points' => $parsedRow['points'],
                        'explanation' => $parsedRow['explanation'],
                        'order' => $maxOrder,
                    ]);

                    $choices = $parsedRow['choices'];

                    // Insert choices
                    foreach ($choices as $choice) {
                        InstitutionQuestionChoice::create([
                            'question_id' => $question->id,
                            'choice_text' => $choice['text'],
                            'is_correct' => $choice['is_correct'],
                            'order' => $choice['order'],
                        ]);
                    }

                    // Save to question bank if user is provided
                    if ($this->user) {
                        $this->saveToQuestionBank($question, $choices, $topicId);
                    }

                    $this->successCount++;
                } catch (\Exception $e) {
                    Log::error("Error importing question at row {$rowNumber}", [
                        'error' => $e->getMessage(),
                        'row' => $row->toArray(),
                    ]);
                    $this->errors[] = str_starts_with($e->getMessage(), "Row {$rowNumber}:")
                        ? $e->getMessage()
                        : "Row {$rowNumber}: {$e->getMessage()}";
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Questions import failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    /**
     * Save imported question to question bank.
     */
    private function saveToQuestionBank(InstitutionQuestion $question, array $choices, ?int $topicId): void
    {
        try {
            // Check if question already exists in question bank
            $existsInBank = QuestionBank::where('question_text', $question->question_text)
                ->where('owner_type', 'institution')
                ->where('organization_id', $this->organizationId)
                ->where('created_by', $this->user->id)
                ->exists();

            if ($existsInBank) {
                Log::info('Question already exists in question bank, skipping', [
                    'question_text' => substr($question->question_text, 0, 50),
                ]);
                return;
            }

            // Create question in question bank
            $bankQuestion = QuestionBank::create([
                'organization_id' => $this->organizationId,
                'owner_type' => 'institution',
                'topic_id' => $topicId,
                'created_by' => $this->user->id,
                'question_type' => $question->question_type,
                'question_text' => $question->question_text,
                'points' => $question->points,
                'image_path' => $question->image_path,
                'is_approved' => false, // New questions need approval
            ]);

            // Create choices in question bank
            foreach ($choices as $idx => $choice) {
                if (!empty($choice['text'])) {
                    $bankQuestion->choices()->create([
                        'choice_text' => $choice['text'],
                        'is_correct' => $choice['is_correct'],
                        'order' => $idx,
                    ]);
                }
            }

            // Initialize statistics
            $bankQuestion->statistics()->create([
                'question_id' => $bankQuestion->id,
                'scope' => 'institution',
                'institution_id' => $this->organizationId,
            ]);

            Log::info('Imported question saved to question bank', [
                'bank_question_id' => $bankQuestion->id,
                'question_id' => $question->id,
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the import
            Log::error('Failed to save imported question to question bank', [
                'error' => $e->getMessage(),
                'question_id' => $question->id,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
