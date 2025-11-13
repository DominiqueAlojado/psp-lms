<?php

namespace App\Imports;

use App\Models\Institution\InstitutionQuestion;
use App\Models\Institution\InstitutionQuestionChoice;
use App\Models\Topic;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class QuestionsImport implements ToCollection, WithHeadingRow
{
    protected int $assessmentId;

    protected int $organizationId;

    protected array $errors = [];

    protected int $successCount = 0;

    public function __construct(int $assessmentId, int $organizationId)
    {
        $this->assessmentId = $assessmentId;
        $this->organizationId = $organizationId;
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
                            $this->errors[] = "Row {$rowNumber}: True/False questions must have 'True' as Choice 1";

                            continue;
                        }
                    } else {
                        if (empty($choice1) || empty($choice2)) {
                            $this->errors[] = "Row {$rowNumber}: At least 2 choices required";

                            continue;
                        }
                    }

                    // Find or create topic if provided
                    $topicId = null;
                    if (! empty($row['topic_optional'])) {
                        $topicName = trim($row['topic_optional']);
                        $slug = \Str::slug($topicName);

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
                        'question_text' => trim($row['question_text']),
                        'points' => (int) $row['points'],
                        'explanation' => ! empty($row['explanation_optional']) ? trim($row['explanation_optional']) : null,
                        'order' => $maxOrder,
                    ]);

                    // Create choices
                    $choices = [
                        ['text' => $choice1, 'is_correct' => true, 'order' => 1],
                        ['text' => $choice2, 'is_correct' => false, 'order' => 2],
                    ];

                    if ($type !== 'true_false') {
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
                            InstitutionQuestionChoice::create([
                                'question_id' => $question->id,
                                'choice_text' => $choice['text'],
                                'is_correct' => $choice['is_correct'],
                                'order' => $choice['order'],
                            ]);
                        }
                    }

                    $this->successCount++;
                } catch (\Exception $e) {
                    Log::error("Error importing question at row {$rowNumber}", [
                        'error' => $e->getMessage(),
                        'row' => $row->toArray(),
                    ]);
                    $this->errors[] = "Row {$rowNumber}: {$e->getMessage()}";
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
}
