<?php

namespace App\Repositories\Eloquent;

use App\Models\Institution\InstitutionQuestion;
use App\Models\Institution\InstitutionQuestionChoice;
use App\Repositories\Contracts\InstitutionQuestionChoiceRepositoryInterface;

class InstitutionQuestionChoiceRepository implements InstitutionQuestionChoiceRepositoryInterface
{
    public function createMany(InstitutionQuestion $question, array $choices): void
    {
        foreach ($choices as $index => $choice) {
            InstitutionQuestionChoice::create([
                'question_id' => $question->id,
                'choice_text' => $choice['choice_text'],
                'is_correct' => $choice['is_correct'],
                'order' => $choice['order'] ?? $index,
            ]);
        }
    }

    public function replaceForQuestion(InstitutionQuestion $question, array $choices): void
    {
        $this->deleteForQuestion($question);
        $this->createMany($question, $choices);
    }

    public function deleteForQuestion(InstitutionQuestion $question): void
    {
        $question->choices()->delete();
    }
}
