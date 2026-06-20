<?php

namespace App\Repositories\Eloquent;

use App\Models\Institution\InstitutionAssessment;
use App\Repositories\Contracts\InstitutionAssessmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class InstitutionAssessmentRepository implements InstitutionAssessmentRepositoryInterface
{
    public function paginateByPublication(int $organizationId, bool $isPublished, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $sort = $filters['sort'] ?? 'created_at';
        $direction = $filters['direction'] ?? 'desc';

        return InstitutionAssessment::query()
            ->where('organization_id', $organizationId)
            ->where('is_published', $isPublished)
            ->with(['questions', 'creator:id,name'])
            ->withCount('questions')
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $nestedQuery) use ($search) {
                    $nestedQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $attributes): InstitutionAssessment
    {
        return InstitutionAssessment::create($attributes);
    }

    public function update(InstitutionAssessment $assessment, array $attributes): bool
    {
        return $assessment->update($attributes);
    }

    public function delete(InstitutionAssessment $assessment): bool
    {
        return (bool) $assessment->delete();
    }

    public function loadForEdit(InstitutionAssessment $assessment): InstitutionAssessment
    {
        return $assessment->load([
            'questions' => fn (Builder $query) => $query->orderBy('order'),
            'questions.choices',
            'creator:id,name',
        ]);
    }

    public function loadForShow(InstitutionAssessment $assessment): InstitutionAssessment
    {
        return $assessment->load(['questions.choices', 'creator:id,name']);
    }

    public function loadQuestionsWithChoices(InstitutionAssessment $assessment): InstitutionAssessment
    {
        return $assessment->load('questions.choices');
    }

    public function attemptsCount(InstitutionAssessment $assessment): int
    {
        return $assessment->attempts()->count();
    }

    public function sumQuestionPoints(InstitutionAssessment $assessment): int
    {
        return (int) $assessment->questions()->sum('points');
    }

    public function titleExists(int $organizationId, string $title): bool
    {
        return InstitutionAssessment::query()
            ->where('organization_id', $organizationId)
            ->where('title', $title)
            ->exists();
    }
}
