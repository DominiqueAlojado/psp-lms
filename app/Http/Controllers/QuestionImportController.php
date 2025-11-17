<?php

namespace App\Http\Controllers;

use App\Imports\QuestionsImport;
use App\Models\Institution\InstitutionAssessment;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class QuestionImportController extends Controller
{
    /**
     * Preview questions from uploaded Excel file.
     */
    public function preview(Request $request, InstitutionAssessment $assessment): JsonResponse
    {
        // Verify user has access
        if ($assessment->organization_id !== $request->user()->current_organization_id) {
            abort(403, 'You do not have access to this assessment.');
        }

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

                // Get topic info
                $topicInfo = null;
                if (! empty($data['topic_optional'])) {
                    $topicName = trim($data['topic_optional']);
                    $slug = Str::slug($topicName);

                    // Check if topic exists
                    $existingTopic = Topic::where('slug', $slug)
                        ->where(function ($query) use ($assessment) {
                            $query->where('organization_id', $assessment->organization_id)
                                ->orWhere('is_global', true);
                        })
                        ->first();

                    $topicInfo = [
                        'name' => $topicName,
                        'exists' => $existingTopic !== null,
                        'will_create' => $existingTopic === null,
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
            \Log::error('Question preview failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to parse file: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Confirm and import questions after preview.
     */
    public function confirmImport(Request $request, InstitutionAssessment $assessment): RedirectResponse
    {
        // Verify user has access
        if ($assessment->organization_id !== $request->user()->current_organization_id) {
            abort(403, 'You do not have access to this assessment.');
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        try {
            $import = new QuestionsImport($assessment->id, $assessment->organization_id, $request->user());
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $errors = $import->getErrors();

            if (count($errors) > 0) {
                $errorMessage = "Imported {$successCount} questions with ".count($errors).' errors: '.implode('; ', array_slice($errors, 0, 3));
                if (count($errors) > 3) {
                    $errorMessage .= '... and '.(count($errors) - 3).' more errors.';
                }

                return back()->with('warning', $errorMessage);
            }

            return back()->with('success', "Successfully imported {$successCount} questions!");
        } catch (\Exception $e) {
            \Log::error('Question import failed', ['error' => $e->getMessage()]);

            return back()->withErrors(['file' => 'Import failed: '.$e->getMessage()]);
        }
    }
}
