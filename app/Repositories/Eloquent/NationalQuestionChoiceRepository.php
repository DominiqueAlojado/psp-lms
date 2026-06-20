<?php

namespace App\Repositories\Eloquent;

use App\Models\National\NationalQuestion;
use App\Models\National\NationalQuestionChoice;
use App\Repositories\Contracts\NationalQuestionChoiceRepositoryInterface;

class NationalQuestionChoiceRepository implements NationalQuestionChoiceRepositoryInterface
{
    public function createMany(NationalQuestion $question, array $choices): void
    {
        foreach ($choices as $index => $choice) {
            NationalQuestionChoice::create([
                'question_id' => $question->id,
                'choice_text' => $choice['choice_text'],
                'is_correct' => $choice['is_correct'],
                'order' => $choice['order'] ?? ($index + 1),
            ]);
        }
    }

    public function replaceForQuestion(NationalQuestion $question, array $choices): void
    {
        $this->deleteForQuestion($question);
        $this->createMany($question, $choices);
    }

    public function deleteForQuestion(NationalQuestion $question): void
    {
        $question->choices()->delete();
    }
}
