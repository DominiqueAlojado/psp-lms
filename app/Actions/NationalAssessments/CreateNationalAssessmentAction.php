<?php

namespace App\Actions\NationalAssessments;

use App\Models\National\NationalAssessment;
use App\Models\User;
use App\Repositories\Contracts\NationalAssessmentRepositoryInterface;

class CreateNationalAssessmentAction
{
    public function __construct(
        private readonly NationalAssessmentRepositoryInterface $assessmentRepository,
    ) {}

    public function execute(User $user, array $validated): NationalAssessment
    {
        return $this->assessmentRepository->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'exam_year' => $validated['exam_year'],
            'exam_period' => $validated['exam_period'],
            'category' => $validated['category'],
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'total_points' => 0,
            'passing_score' => $validated['passing_score'],
            'randomize_questions' => $validated['randomize_questions'] ?? false,
            'randomize_choices' => $validated['randomize_choices'] ?? false,
            'show_results_immediately' => $validated['show_results_immediately'] ?? false,
            'allow_review' => $validated['allow_review'] ?? false,
            'is_published' => $validated['is_published'] ?? false,
            'national_ranking_enabled' => $validated['national_ranking_enabled'] ?? true,
            'institution_comparison_enabled' => $validated['institution_comparison_enabled'] ?? true,
            'scheduled_date' => $validated['scheduled_date'] ?? null,
            'results_release_date' => $validated['results_release_date'] ?? null,
            'created_by' => $user->id,
        ]);
    }
}
