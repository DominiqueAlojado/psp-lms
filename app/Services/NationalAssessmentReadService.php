<?php

namespace App\Services;

use App\Models\National\NationalAssessment;
use App\Models\Topic;
use App\Repositories\Contracts\NationalAssessmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class NationalAssessmentReadService
{
    public function __construct(
        private readonly NationalAssessmentRepositoryInterface $assessmentRepository,
    ) {}

    public function list(array $filters): LengthAwarePaginator
    {
        return $this->assessmentRepository
            ->paginate($filters)
            ->through(fn (NationalAssessment $assessment) => $this->toIndexItem($assessment));
    }

    public function active(array $filters): LengthAwarePaginator
    {
        return $this->assessmentRepository
            ->paginateByPublication(true, $filters)
            ->through(fn (NationalAssessment $assessment) => $this->toIndexItem($assessment));
    }

    public function drafts(array $filters): LengthAwarePaginator
    {
        return $this->assessmentRepository
            ->paginateByPublication(false, $filters)
            ->through(fn (NationalAssessment $assessment) => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'description' => $assessment->description,
                'exam_year' => $assessment->exam_year,
                'exam_period' => $assessment->exam_period,
                'questions_count' => $assessment->questions_count,
                'total_points' => $assessment->total_points,
                'passing_score' => $assessment->passing_score,
                'duration_minutes' => $assessment->duration_minutes,
                'is_published' => $assessment->is_published,
                'created_by' => $assessment->creator?->name,
                'created_at' => $assessment->created_at?->format('Y-m-d'),
            ]);
    }

    public function years(): array
    {
        return $this->assessmentRepository->getDistinctYears();
    }

    public function editPayload(NationalAssessment $assessment): array
    {
        $assessment = $this->assessmentRepository->loadForEdit($assessment);
        $topicIdsByName = Topic::query()
            ->whereIn('name', $assessment->questions->pluck('topic')->filter()->unique()->all())
            ->pluck('id', 'name');

        return [
            'assessment' => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'description' => $assessment->description,
                'category' => $assessment->category,
                'duration_minutes' => $assessment->duration_minutes,
                'total_points' => $assessment->total_points,
                'passing_score' => $assessment->passing_score,
                'randomize_questions' => $assessment->randomize_questions,
                'randomize_choices' => $assessment->randomize_choices,
                'show_results_immediately' => $assessment->show_results_immediately,
                'allow_review' => $assessment->allow_review,
                'is_published' => $assessment->is_published,
                'available_from' => $assessment->scheduled_date?->format('Y-m-d\TH:i'),
                'available_until' => $assessment->results_release_date?->format('Y-m-d\TH:i'),
                'questions' => $assessment->questions->map(fn ($question) => [
                    'id' => $question->id,
                    'question_type' => $question->question_type,
                    'question_text' => $question->question_text,
                    'points' => $question->points,
                    'topic' => $question->topic,
                    'topic_id' => $question->topic ? $topicIdsByName->get($question->topic) : null,
                    'order' => $question->order,
                    'image_path' => $question->image_path,
                    'image_url' => $question->image_path ? Storage::url($question->image_path) : null,
                    'choices' => $question->choices->map(fn ($choice) => [
                        'id' => $choice->id,
                        'choice_text' => $choice->choice_text,
                        'is_correct' => $choice->is_correct,
                        'order' => $choice->order,
                    ]),
                ]),
                'created_by' => $assessment->creator?->name,
                'created_at' => $assessment->created_at?->format('Y-m-d'),
            ],
        ];
    }

    public function showPayload(NationalAssessment $assessment): array
    {
        $assessment = $this->assessmentRepository->loadForShow($assessment);

        return [
            'assessment' => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'description' => $assessment->description,
                'exam_year' => $assessment->exam_year,
                'exam_period' => $assessment->exam_period,
                'category' => $assessment->category,
                'duration_minutes' => $assessment->duration_minutes,
                'total_points' => $assessment->total_points,
                'passing_score' => $assessment->passing_score,
                'is_published' => $assessment->is_published,
                'questions' => $assessment->questions->map(fn ($question) => [
                    'id' => $question->id,
                    'question_type' => $question->question_type,
                    'question_text' => $question->question_text,
                    'points' => $question->points,
                    'explanation' => $question->explanation,
                    'order' => $question->order,
                    'choices' => $question->choices->map(fn ($choice) => [
                        'id' => $choice->id,
                        'choice_text' => $choice->choice_text,
                        'is_correct' => $choice->is_correct,
                        'order' => $choice->order,
                    ]),
                ]),
                'created_by' => $assessment->creator?->name,
                'created_at' => $assessment->created_at?->format('Y-m-d'),
            ],
        ];
    }

    private function toIndexItem(NationalAssessment $assessment): array
    {
        return [
            'id' => $assessment->id,
            'title' => $assessment->title,
            'description' => $assessment->description,
            'exam_year' => $assessment->exam_year,
            'exam_period' => $assessment->exam_period,
            'questions_count' => $assessment->questions_count,
            'total_points' => $assessment->total_points,
            'passing_score' => $assessment->passing_score,
            'duration_minutes' => $assessment->duration_minutes,
            'is_published' => $assessment->is_published,
            'is_available' => $assessment->isAvailable(),
            'scheduled_date' => $assessment->scheduled_date?->format('Y-m-d H:i'),
            'results_release_date' => $assessment->results_release_date?->format('Y-m-d H:i'),
            'can_view_results' => $assessment->canViewResults(),
            'national_ranking_enabled' => $assessment->national_ranking_enabled,
            'created_by' => $assessment->creator?->name,
            'created_at' => $assessment->created_at?->format('Y-m-d'),
            'updated_at' => $assessment->updated_at?->diffForHumans(),
        ];
    }
}
