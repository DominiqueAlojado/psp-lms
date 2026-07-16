<?php

namespace App\Services;

use App\Models\Institution\InstitutionAssessment;
use App\Models\User;
use App\Repositories\Contracts\InstitutionAssessmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class InstitutionAssessmentReadService
{
    private const ALL_ORGANIZATIONS_SLUG = 'all-organizations';

    public function __construct(
        private readonly InstitutionAssessmentRepositoryInterface $assessmentRepository,
    ) {}

    public function shouldRedirectToInservice(User $user): bool
    {
        return $user->currentOrganization?->type === 'national';
    }

    public function listForPublication(User $user, bool $isPublished, array $filters): LengthAwarePaginator
    {
        $includeAllOrganizations = $this->includeAllOrganizations($user);

        return $this->assessmentRepository
            ->paginateByPublication(
                $user->current_organization_id,
                $isPublished,
                $filters,
                includeAllOrganizations: $includeAllOrganizations,
            )
            ->through(fn (InstitutionAssessment $assessment) => $this->toListItem($assessment));
    }

    public function canAccess(User $user, InstitutionAssessment $assessment): bool
    {
        if ($this->includeAllOrganizations($user) && $user->hasAnyRole(['System Admin', 'BOP'])) {
            return true;
        }

        return $assessment->organization_id === $user->current_organization_id;
    }

    public function editPayload(InstitutionAssessment $assessment): array
    {
        $assessment = $this->assessmentRepository->loadForEdit($assessment);
        $publicDisk = Storage::disk('public');

        return [
            'assessment' => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'description' => $assessment->description,
                'exam_category' => $assessment->exam_category,
                'duration_minutes' => $assessment->duration_minutes,
                'total_points' => $assessment->total_points,
                'passing_score' => $assessment->passing_score,
                'randomize_questions' => $assessment->randomize_questions,
                'randomize_choices' => $assessment->randomize_choices,
                'show_results_immediately' => $assessment->show_results_immediately,
                'allow_review' => $assessment->allow_review,
                'is_published' => $assessment->is_published,
                'available_from' => $assessment->available_from?->format('Y-m-d\TH:i'),
                'available_until' => $assessment->available_until?->format('Y-m-d\TH:i'),
                'questions' => $assessment->questions->map(fn ($question) => [
                    'id' => $question->id,
                    'topic_id' => $question->topic_id,
                    'question_type' => $question->question_type,
                    'question_text' => $question->question_text,
                    'points' => $question->points,
                    'explanation' => $question->explanation,
                    'image_path' => $question->image_path,
                    'image_url' => $question->image_path ? $publicDisk->url($question->image_path) : null,
                    'order' => $question->order,
                    'choices' => $question->choices->map(fn ($choice) => [
                        'id' => $choice->id,
                        'choice_text' => $choice->choice_text,
                        'is_correct' => $choice->is_correct,
                        'order' => $choice->order,
                    ]),
                ]),
                'created_by' => $assessment->creator->name,
                'created_at' => $assessment->created_at->format('Y-m-d'),
            ],
            'questionBankScope' => $assessment->organization?->type === 'national' ? 'national' : 'institution',
        ];
    }

    public function showPayload(InstitutionAssessment $assessment): array
    {
        $assessment = $this->assessmentRepository->loadForShow($assessment);

        return [
            'assessment' => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'description' => $assessment->description,
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
                'created_by' => $assessment->creator->name,
                'created_at' => $assessment->created_at->format('Y-m-d'),
            ],
        ];
    }

    private function toListItem(InstitutionAssessment $assessment): array
    {
        return [
            'id' => $assessment->id,
            'title' => $assessment->title,
            'description' => $assessment->description,
            'exam_category' => $assessment->exam_category,
            'questions_count' => $assessment->questions_count,
            'total_points' => $assessment->total_points,
            'passing_score' => $assessment->passing_score,
            'duration_minutes' => $assessment->duration_minutes,
            'is_published' => $assessment->is_published,
            'is_available' => $assessment->isAvailable(),
            'available_from' => $assessment->available_from?->format('Y-m-d H:i'),
            'available_until' => $assessment->available_until?->format('Y-m-d H:i'),
            'created_by' => $assessment->creator->name,
            'organization_name' => $assessment->organization?->name,
            'created_at' => $assessment->created_at->format('Y-m-d'),
            'updated_at' => $assessment->updated_at->toIso8601String(),
        ];
    }

    private function includeAllOrganizations(User $user): bool
    {
        return request()->query('org') === self::ALL_ORGANIZATIONS_SLUG
            && $user->hasAnyRole(['System Admin', 'BOP']);
    }
}
