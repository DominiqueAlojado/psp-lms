<?php

namespace App\Services;

use App\Models\Institution\InstitutionAssessment;
use App\Models\User;
use App\Repositories\Contracts\InstitutionAssessmentRepositoryInterface;
use Illuminate\Support\Facades\Log;

class InstitutionAssessmentManagementService
{
    public function __construct(
        private readonly InstitutionAssessmentRepositoryInterface $assessmentRepository,
    ) {}

    public function canCreateFromCurrentOrganization(User $user): bool
    {
        return $user->currentOrganization?->type !== 'national';
    }

    public function create(User $user, array $validated): InstitutionAssessment
    {
        $assessment = $this->assessmentRepository->create([
            'organization_id' => $user->current_organization_id,
            'created_by' => $user->id,
            'total_points' => 0,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'exam_category' => $validated['exam_category'] ?? null,
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'passing_score' => $validated['passing_score'],
            'randomize_questions' => $validated['randomize_questions'] ?? false,
            'randomize_choices' => $validated['randomize_choices'] ?? false,
            'show_results_immediately' => $validated['show_results_immediately'] ?? true,
            'allow_review' => $validated['allow_review'] ?? true,
            'available_from' => $validated['available_from'] ?? null,
            'available_until' => $validated['available_until'] ?? null,
            'is_published' => $validated['is_published'] ?? false,
        ]);

        Log::info('Exam created successfully', ['id' => $assessment->id]);

        return $assessment;
    }

    public function update(InstitutionAssessment $assessment, array $validated): void
    {
        if ($assessment->organization?->type === 'national') {
            $validated['exam_category'] = 'In-service';
        }

        $this->assessmentRepository->update($assessment, $validated);
    }

    public function canDelete(InstitutionAssessment $assessment): bool
    {
        return $this->assessmentRepository->attemptsCount($assessment) === 0;
    }

    public function delete(InstitutionAssessment $assessment): bool
    {
        return $this->assessmentRepository->delete($assessment);
    }
}
