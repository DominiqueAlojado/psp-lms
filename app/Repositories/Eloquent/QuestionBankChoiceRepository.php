<?php

namespace App\Repositories\Eloquent;

use App\Models\QuestionBank;
use App\Models\QuestionBankChoice;
use App\Repositories\Contracts\QuestionBankChoiceRepositoryInterface;

class QuestionBankChoiceRepository implements QuestionBankChoiceRepositoryInterface
{
    public function createMany(QuestionBank $question, array $choices): void
    {
        foreach ($choices as $index => $choice) {
            QuestionBankChoice::create([
                'question_id' => $question->id,
                'choice_text' => $choice['choice_text'],
                'is_correct' => $choice['is_correct'],
                'order' => $index,
            ]);
        }
    }

    public function replaceForQuestion(QuestionBank $question, array $choices): void
    {
        $question->choices()->delete();
        $this->createMany($question, $choices);
    }
}
