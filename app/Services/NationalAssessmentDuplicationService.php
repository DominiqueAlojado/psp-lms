<?php

namespace App\Services;

use App\Models\National\NationalAssessment;
use App\Repositories\Contracts\NationalAssessmentRepositoryInterface;
use App\Repositories\Contracts\NationalQuestionChoiceRepositoryInterface;
use App\Repositories\Contracts\NationalQuestionRepositoryInterface;
use App\Services\ActivityLog\NationalAssessmentActivityLogService;
use Illuminate\Support\Facades\DB;

class NationalAssessmentDuplicationService
{
    public function __construct(
        private readonly NationalAssessmentRepositoryInterface $assessmentRepository,
        private readonly NationalQuestionRepositoryInterface $questionRepository,
        private readonly NationalQuestionChoiceRepositoryInterface $questionChoiceRepository,
        private readonly NationalAssessmentActivityLogService $activityLogService,
    ) {}

    public function duplicate(NationalAssessment $assessment, int $userId, ?string $title = null): NationalAssessment
    {
        $assessment = $this->assessmentRepository->loadQuestionsWithChoices($assessment);

        $duplicate = DB::transaction(function () use ($assessment, $userId, $title) {
            $duplicate = $this->assessmentRepository->create([
                'title' => $title ?: $assessment->title . ' (Copy)',
                'description' => $assessment->description,
                'exam_year' => $assessment->exam_year,
                'exam_period' => $assessment->exam_period,
                'category' => $assessment->category,
                'duration_minutes' => $assessment->duration_minutes,
                'total_points' => 0,
                'passing_score' => $assessment->passing_score,
                'randomize_questions' => $assessment->randomize_questions,
                'randomize_choices' => $assessment->randomize_choices,
                'show_results_immediately' => $assessment->show_results_immediately,
                'allow_review' => $assessment->allow_review,
                'is_published' => false,
                'national_ranking_enabled' => $assessment->national_ranking_enabled,
                'institution_comparison_enabled' => $assessment->institution_comparison_enabled,
                'scheduled_date' => null,
                'results_release_date' => null,
                'created_by' => $userId,
            ]);

            foreach ($assessment->questions as $question) {
                $newQuestion = $this->questionRepository->createForAssessment($duplicate, [
                    'question_type' => $question->question_type,
                    'question_text' => $question->question_text,
                    'points' => $question->points,
                    'explanation' => $question->explanation,
                    'image_path' => $question->image_path,
                    'difficulty_level' => $question->difficulty_level,
                    'topic' => $question->topic,
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
            }

            $this->assessmentRepository->update($duplicate, [
                'total_points' => $this->assessmentRepository->sumQuestionPoints($duplicate),
            ]);

            return $duplicate;
        });

        $this->activityLogService->logAssessmentDuplicated($assessment, $duplicate);

        return $duplicate;
    }
}
