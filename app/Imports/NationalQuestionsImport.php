<?php

namespace App\Imports;

use App\Models\National\NationalQuestion;
use App\Models\National\NationalQuestionChoice;
use App\Models\QuestionBank;
use App\Models\Topic;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class NationalQuestionsImport implements ToCollection, WithHeadingRow
{
    protected int $assessmentId;

    protected $user;

    protected array $errors = [];

    protected int $successCount = 0;

    public function __construct(int $assessmentId, $user = null)
    {
        $this->assessmentId = $assessmentId;
        $this->user = $user;
    }

    public function collection(Collection $rows): void
    {
        DB::beginTransaction();

        try {
            // Get the current max order for questions
            $maxOrder = NationalQuestion::where('assessment_id', $this->assessmentId)
                ->max('order') ?? 0;

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 because: +1 for 0-index, +1 for header row

                try {
                    // Validate required fields
                    if (empty($row['question_text']) || empty($row['type']) || empty($row['points'])) {
                        $this->errors[] = "Row {$rowNumber}: Missing required fields (Question Text, Type, or Points)";

                        continue;
                    }

                    // Validate question type
                    $type = strtolower(trim($row['type']));
                    if (! in_array($type, ['multiple_choice', 'multiple_select', 'true_false'])) {
                        $this->errors[] = "Row {$rowNumber}: Invalid type '{$row['type']}'. Must be: multiple_choice, multiple_select, or true_false";

                        continue;
                    }

                    // Validate choices based on type
                    $choice1 = trim($row['choice_1_correct_answer'] ?? '');
                    $choice2 = trim($row['choice_2'] ?? '');

                    if ($type === 'true_false') {
                        if (! in_array(strtolower($choice1), ['true', 'false'])) {
                            $this->errors[] = "Row {$rowNumber}: True/False questions must have 'True' or 'False' as Choice 1";

                            continue;
                        }
                    } else {
                        if (empty($choice1) || empty($choice2)) {
                            $this->errors[] = "Row {$rowNumber}: At least 2 choices required";

                            continue;
                        }
                    }

                    // Find topic by name if provided (national exams use topic as string, not topic_id)
                    $topicName = null;
                    $topicId = null;
                    if (! empty($row['topic_optional'])) {
                        $topicName = trim($row['topic_optional']);
                        // Try to find existing topic
                        $topic = Topic::where('name', $topicName)->first();
                        $topicId = $topic?->id;
                    }

                    // Create the question
                    $maxOrder++;
                    $question = NationalQuestion::create([
                        'assessment_id' => $this->assessmentId,
                        'question_type' => $type,
                        'question_text' => trim($row['question_text']),
                        'points' => (int) $row['points'],
                        'topic' => $topicName,
                        'order' => $maxOrder,
                    ]);

                    // Create choices
                    $choices = [];
                    if ($type === 'true_false') {
                        $answer = strtolower($choice1) === 'true';
                        $choices = [
                            ['text' => 'True', 'is_correct' => $answer === true, 'order' => 1],
                            ['text' => 'False', 'is_correct' => $answer === false, 'order' => 2],
                        ];
                    } else {
                        $choices = [
                            ['text' => $choice1, 'is_correct' => true, 'order' => 1],
                            ['text' => $choice2, 'is_correct' => false, 'order' => 2],
                        ];

                        $choice3 = trim($row['choice_3'] ?? '');
                        $choice4 = trim($row['choice_4'] ?? '');

                        if (! empty($choice3)) {
                            $choices[] = ['text' => $choice3, 'is_correct' => false, 'order' => 3];
                        }
                        if (! empty($choice4)) {
                            $choices[] = ['text' => $choice4, 'is_correct' => false, 'order' => 4];
                        }
                    }

                    // Insert choices
                    foreach ($choices as $choice) {
                        if (! empty($choice['text'])) {
                            NationalQuestionChoice::create([
                                'question_id' => $question->id,
                                'choice_text' => $choice['text'],
                                'is_correct' => $choice['is_correct'],
                                'order' => $choice['order'],
                            ]);
                        }
                    }

                    // Save to question bank if user is provided
                    if ($this->user) {
                        $this->saveToQuestionBank($question, $choices, $topicId, $topicName);
                    }

                    $this->successCount++;
                } catch (\Exception $e) {
                    Log::error("Error importing national question at row {$rowNumber}", [
                        'error' => $e->getMessage(),
                        'row' => $row->toArray(),
                    ]);
                    $this->errors[] = "Row {$rowNumber}: {$e->getMessage()}";
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('National questions import failed', ['error' => $e->getMessage()]);
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
    private function saveToQuestionBank(NationalQuestion $question, array $choices, ?int $topicId, ?string $topicName): void
    {
        try {
            // Check if question already exists in question bank
            $existsInBank = QuestionBank::where('question_text', $question->question_text)
                ->where('owner_type', 'national')
                ->where('created_by', $this->user->id)
                ->exists();

            if ($existsInBank) {
                Log::info('National question already exists in question bank, skipping', [
                    'question_text' => substr($question->question_text, 0, 50),
                ]);
                return;
            }

            // Use topic_id if available, otherwise try to find by name
            if (! $topicId && $topicName) {
                $topic = Topic::where('name', $topicName)->first();
                $topicId = $topic?->id;
            }

            // Create question in question bank
            $bankQuestion = QuestionBank::create([
                'organization_id' => null, // National questions don't belong to a specific organization
                'owner_type' => 'national',
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
                'scope' => 'national',
                'institution_id' => null,
            ]);

            Log::info('Imported national question saved to question bank', [
                'bank_question_id' => $bankQuestion->id,
                'question_id' => $question->id,
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the import
            Log::error('Failed to save imported national question to question bank', [
                'error' => $e->getMessage(),
                'question_id' => $question->id,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
