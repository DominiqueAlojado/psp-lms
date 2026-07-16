<?php

namespace App\Repositories\Eloquent;

use App\Models\National\NationalAssessment;
use App\Repositories\Contracts\NationalAssessmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class NationalAssessmentRepository implements NationalAssessmentRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->baseFilteredQuery($filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateByPublication(bool $isPublished, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->baseFilteredQuery($filters)
            ->where('is_published', $isPublished)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getDistinctYears(): array
    {
        return NationalAssessment::query()
            ->select('exam_year')
            ->distinct()
            ->orderByDesc('exam_year')
            ->pluck('exam_year')
            ->toArray();
    }

    public function create(array $attributes): NationalAssessment
    {
        return NationalAssessment::create($attributes);
    }

    public function update(NationalAssessment $assessment, array $attributes): bool
    {
        return $assessment->update($attributes);
    }

    public function delete(NationalAssessment $assessment): bool
    {
        return (bool) $assessment->delete();
    }

    public function loadForEdit(NationalAssessment $assessment): NationalAssessment
    {
        return $assessment->load($this->assessmentDetailRelations(true));
    }

    public function loadForShow(NationalAssessment $assessment): NationalAssessment
    {
        return $assessment->load($this->assessmentDetailRelations(false));
    }

    public function loadQuestionsWithChoices(NationalAssessment $assessment): NationalAssessment
    {
        return $assessment->load('questions.choices');
    }

    public function attemptsExist(NationalAssessment $assessment): bool
    {
        return $assessment->attempts()->exists();
    }

    public function sumQuestionPoints(NationalAssessment $assessment): int
    {
        return (int) $assessment->questions()->sum('points');
    }

    private function baseFilteredQuery(array $filters): Builder
    {
        $sort = $filters['sort'] ?? 'created_at';
        $direction = $filters['direction'] ?? 'desc';

        return NationalAssessment::query()
            ->with(['creator:id,name'])
            ->withCount('questions')
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $nestedQuery) use ($search) {
                    $nestedQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['year'] ?? null, function (Builder $query, $year) {
                $query->where('exam_year', $year);
            })
            ->when($filters['status'] ?? null, function (Builder $query, string $status) {
                if ($status === 'published') {
                    $query->where('is_published', true);
                } elseif ($status === 'draft') {
                    $query->where('is_published', false);
                }
            })
            ->orderBy($sort, $direction);
    }

    private function assessmentDetailRelations(bool $includeTopicRelation): array
    {
        $relations = [
            'questions' => fn ($query) => $query
                ->select([
                    'id',
                    'assessment_id',
                    'question_type',
                    'question_text',
                    'points',
                    'explanation',
                    'image_path',
                    'topic',
                    'topic_id',
                    'order',
                ])
                ->orderBy('order'),
            'questions.choices' => fn ($query) => $query
                ->select(['id', 'question_id', 'choice_text', 'is_correct', 'order'])
                ->orderBy('order'),
            'creator:id,name',
        ];

        if ($includeTopicRelation) {
            $relations[] = 'questions.topicRecord:id,name';
        }

        return $relations;
    }
}
