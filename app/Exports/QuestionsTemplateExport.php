<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QuestionsTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function array(): array
    {
        // Return sample data to help users understand the format
        return [
            [
                'What is the most common type of anemia?',
                'multiple_choice',
                1,
                'Iron deficiency anemia',
                'Vitamin B12 deficiency',
                'Folate deficiency',
                'Hemolytic anemia',
                '',
                'Iron deficiency is the most common cause worldwide',
                'Hematology',
            ],
            [
                'Which organs are part of the digestive system? (Select all)',
                'multiple_select',
                2,
                'Stomach',
                'Liver',
                'Heart',
                'Lungs',
                '1,2',
                'Stomach and liver are digestive organs',
                'Anatomy',
            ],
            [
                'The human heart has four chambers.',
                'true_false',
                1,
                'True',
                'False',
                '',
                '',
                '',
                'The heart has two atria and two ventricles',
                'Cardiology',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'question_text',
            'type',
            'points',
            'choice_1_correct_answer',
            'choice_2',
            'choice_3',
            'choice_4',
            'correct_choice_numbers_optional',
            'explanation_optional',
            'topic_optional',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E2E8F0']],
            ],
        ];
    }
}
