<?php

namespace App\Actions\InstitutionExams;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionQuestion;
use App\Repositories\Contracts\InstitutionAssessmentRepositoryInterface;
use App\Repositories\Contracts\InstitutionQuestionRepositoryInterface;
use Illuminate\Support\Facades\DB;

class DeleteInstitutionQuestionsAction
{
    public function __construct(
        private readonly InstitutionAssessmentRepositoryInterface $assessmentRepository,
        private readonly InstitutionQuestionRepositoryInterface $questionRepository,
    ) {}

    public function deleteOne(InstitutionAssessment $assessment, InstitutionQuestion $question): void
    {
        DB::transaction(function () use ($assessment, $question) {
            $this->questionRepository->delete($question);
            $this->assessmentRepository->update($assessment, [
                'total_points' => $this->assessmentRepository->sumQuestionPoints($assessment),
            ]);
        });
    }

    public function deleteMany(InstitutionAssessment $assessment, array $questionIds): int
    {
        $uniqueQuestionIds = collect($questionIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        DB::transaction(function () use ($assessment, $uniqueQuestionIds) {
            $this->questionRepository->deleteForAssessmentByIds($assessment, $uniqueQuestionIds->all());
            $this->assessmentRepository->update($assessment, [
                'total_points' => $this->assessmentRepository->sumQuestionPoints($assessment),
            ]);
        });

        return $uniqueQuestionIds->count();
    }
}
