<?php

namespace App\Actions\InstitutionExams;

use App\Models\Institution\InstitutionAssessment;
use App\Repositories\Contracts\InstitutionAssessmentRepositoryInterface;
use App\Repositories\Contracts\InstitutionQuestionChoiceRepositoryInterface;
use App\Repositories\Contracts\InstitutionQuestionRepositoryInterface;
use Illuminate\Support\Facades\DB;

class DuplicateInstitutionAssessmentAction
{
    public function __construct(
        private readonly InstitutionAssessmentRepositoryInterface $assessmentRepository,
        private readonly InstitutionQuestionRepositoryInterface $questionRepository,
        private readonly InstitutionQuestionChoiceRepositoryInterface $questionChoiceRepository,
    ) {}

    public function execute(
        InstitutionAssessment $assessment,
        int $createdBy,
        string $title,
    ): InstitutionAssessment {
        return DB::transaction(function () use ($assessment, $createdBy, $title) {
            $duplicate = $this->assessmentRepository->create([
                'organization_id' => $assessment->organization_id,
                'created_by' => $createdBy,
                'title' => $title,
                'description' => $assessment->description,
                'exam_category' => $assessment->exam_category,
                'course_id' => $assessment->course_id,
                'duration_minutes' => $assessment->duration_minutes,
                'total_points' => 0,
                'passing_score' => $assessment->passing_score,
                'randomize_questions' => $assessment->randomize_questions,
                'randomize_choices' => $assessment->randomize_choices,
                'show_results_immediately' => $assessment->show_results_immediately,
                'allow_review' => $assessment->allow_review,
                'available_from' => null,
                'available_until' => null,
                'is_published' => false,
            ]);

            $totalPoints = 0;

            foreach ($assessment->questions as $question) {
                $newQuestion = $this->questionRepository->createForAssessment($duplicate, [
                    'topic_id' => $question->topic_id,
                    'question_type' => $question->question_type,
                    'question_text' => $question->question_text,
                    'points' => $question->points,
                    'explanation' => $question->explanation,
                    'image_path' => $question->image_path,
                    'order' => $question->order,
                ]);

                $this->questionChoiceRepository->createMany(
                    $newQuestion,
                    $question->choices->map(fn ($choice) => [
                        'choice_text' => $choice->choice_text,
                        'is_correct' => $choice->is_correct,
                        'order' => $choice->order,
                    ])->all()
                );

                $totalPoints += $newQuestion->points;
            }

            $this->assessmentRepository->update($duplicate, ['total_points' => $totalPoints]);

            return $duplicate->fresh('questions.choices');
        });
    }
}
