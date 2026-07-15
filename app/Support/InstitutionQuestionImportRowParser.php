<?php

namespace App\Support;

class InstitutionQuestionImportRowParser
{
    /**
     * @return array{
     *     question_text:string,
     *     type:string,
     *     points:int,
     *     explanation:?string,
     *     choices:array<int,array{text:string,is_correct:bool,order:int}>
     * }
     */
    public function parse(array $data, int $rowNumber): array
    {
        if (empty($data['question_text']) || empty($data['type']) || empty($data['points'])) {
            throw new \RuntimeException("Row {$rowNumber}: Missing required fields");
        }

        $type = strtolower(trim((string) $data['type']));
        if (! in_array($type, ['multiple_choice', 'multiple_select', 'true_false'], true)) {
            throw new \RuntimeException("Row {$rowNumber}: Invalid type '{$data['type']}'");
        }

        return [
            'question_text' => trim((string) $data['question_text']),
            'type' => $type,
            'points' => (int) $data['points'],
            'explanation' => ! empty($data['explanation_optional']) ? trim((string) $data['explanation_optional']) : null,
            'choices' => $this->parseChoices($type, $data, $rowNumber),
        ];
    }

    /**
     * @return array<int,array{text:string,is_correct:bool,order:int}>
     */
    private function parseChoices(string $type, array $data, int $rowNumber): array
    {
        if ($type === 'true_false') {
            $correctAnswer = strtolower(trim((string) ($data['choice_1_correct_answer'] ?? '')));

            if (! in_array($correctAnswer, ['true', 'false'], true)) {
                throw new \RuntimeException("Row {$rowNumber}: True/False questions must use 'true' or 'false' in choice_1_correct_answer");
            }

            return [
                ['text' => 'True', 'is_correct' => $correctAnswer === 'true', 'order' => 1],
                ['text' => 'False', 'is_correct' => $correctAnswer === 'false', 'order' => 2],
            ];
        }

        $choiceTexts = [
            1 => trim((string) ($data['choice_1'] ?? $data['choice_1_correct_answer'] ?? '')),
            2 => trim((string) ($data['choice_2'] ?? '')),
            3 => trim((string) ($data['choice_3'] ?? '')),
            4 => trim((string) ($data['choice_4'] ?? '')),
        ];

        if ($choiceTexts[1] === '' || $choiceTexts[2] === '') {
            throw new \RuntimeException("Row {$rowNumber}: At least 2 choices required");
        }

        $providedChoices = array_filter($choiceTexts, fn ($text) => $text !== '');
        $correctChoiceNumbers = $this->parseCorrectChoiceNumbers($type, $data, $rowNumber, array_keys($providedChoices));

        return collect($providedChoices)
            ->map(fn (string $text, int $order) => [
                'text' => $text,
                'is_correct' => in_array($order, $correctChoiceNumbers, true),
                'order' => $order,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int,int>  $availableChoiceNumbers
     * @return array<int,int>
     */
    private function parseCorrectChoiceNumbers(string $type, array $data, int $rowNumber, array $availableChoiceNumbers): array
    {
        $rawCorrectChoices = trim((string) ($data['correct_choice_numbers_optional'] ?? ''));

        if ($rawCorrectChoices === '') {
            if ($type === 'multiple_select') {
                throw new \RuntimeException("Row {$rowNumber}: Multiple select questions must define correct choices in correct_choice_numbers_optional");
            }

            return [1];
        }

        $correctChoiceNumbers = collect(explode(',', $rawCorrectChoices))
            ->map(fn (string $value) => trim($value))
            ->filter()
            ->map(function (string $value) use ($rowNumber) {
                if (! ctype_digit($value)) {
                    throw new \RuntimeException("Row {$rowNumber}: correct_choice_numbers_optional must contain comma-separated choice numbers");
                }

                return (int) $value;
            })
            ->unique()
            ->values()
            ->all();

        if ($correctChoiceNumbers === []) {
            throw new \RuntimeException("Row {$rowNumber}: correct_choice_numbers_optional must contain at least one choice number");
        }

        if (array_diff($correctChoiceNumbers, $availableChoiceNumbers) !== []) {
            throw new \RuntimeException("Row {$rowNumber}: correct_choice_numbers_optional references a choice that was not provided");
        }

        if ($type === 'multiple_choice' && count($correctChoiceNumbers) !== 1) {
            throw new \RuntimeException("Row {$rowNumber}: Multiple choice questions must have exactly one correct choice");
        }

        return $correctChoiceNumbers;
    }
}
