<?php

namespace App\Repositories\Eloquent;

use App\Models\QuestionBank;
use App\Repositories\Contracts\QuestionBankRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class QuestionBankRepository implements QuestionBankRepositoryInterface
{
    public function paginateScoped(?int $organizationId, bool $isNational, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->scopedQuery($organizationId, $isNational, true)
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where('question_text', 'like', "%{$search}%");
            })
            ->when($filters['topic'] ?? null, function (Builder $query, $topicId) {
                $query->where('topic_id', $topicId);
            })
            ->when($filters['type'] ?? null, function (Builder $query, string $type) {
                $query->where('question_type', $type);
            })
            ->when($filters['difficulty'] ?? null, function (Builder $query, string $difficulty) {
                if ($difficulty === 'manual') {
                    $query->whereNotNull('difficulty_level');
                } else {
                    $query->whereHas('statistics', function (Builder $statisticsQuery) use ($difficulty) {
                        $statisticsQuery->where('computed_difficulty', $difficulty);
                    });
                }
            })
            ->when($filters['approval'] ?? null, function (Builder $query, string $approval) {
                if ($approval === 'approved') {
                    $query->where('is_approved', true);
                } elseif ($approval === 'pending') {
                    $query->where('is_approved', false);
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function listScoped(?int $organizationId, bool $isNational, array $filters, int $limit = 100): Collection
    {
        return $this->scopedQuery($organizationId, $isNational)
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where('question_text', 'like', "%{$search}%");
            })
            ->when($filters['topic'] ?? null, function (Builder $query, $topicId) {
                $query->where('topic_id', $topicId);
            })
            ->when($filters['type'] ?? null, function (Builder $query, string $type) {
                $query->where('question_type', $type);
            })
            ->when($filters['difficulty'] ?? null, function (Builder $query, string $difficulty) {
                if ($difficulty === 'manual') {
                    $query->whereNotNull('difficulty_level');
                } else {
                    $query->whereHas('statistics', function (Builder $statisticsQuery) use ($difficulty) {
                        $statisticsQuery->where('computed_difficulty', $difficulty);
                    });
                }
            })
            ->when($filters['approval'] ?? null, function (Builder $query, string $approval) {
                if ($approval === 'approved') {
                    $query->where('is_approved', true);
                } elseif ($approval === 'pending') {
                    $query->where('is_approved', false);
                }
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function create(array $attributes): QuestionBank
    {
        return QuestionBank::create($attributes);
    }

    public function update(QuestionBank $question, array $attributes): bool
    {
        return $question->update($attributes);
    }

    public function delete(QuestionBank $question): bool
    {
        return (bool) $question->delete();
    }

    public function assessmentsCount(QuestionBank $question): int
    {
        return $question->assessments()->count();
    }

    public function approve(QuestionBank $question, int $approvedBy): bool
    {
        return $question->update([
            'is_approved' => true,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    public function getStatisticsData(?int $organizationId, bool $isNational): array
    {
        $baseQuery = QuestionBank::query();

        if ($isNational) {
            $baseQuery->national();
        } else {
            $baseQuery->institution()->forOrganization($organizationId);
        }

        return [
            'totalQuestions' => (clone $baseQuery)->count(),
            'approvedQuestions' => (clone $baseQuery)->approved()->count(),
            'byType' => (clone $baseQuery)
                ->selectRaw('question_type, COUNT(*) as count')
                ->groupBy('question_type')
                ->get(),
            'topPerforming' => (clone $baseQuery)
                ->with(['topic', 'statistics'])
                ->whereHas('statistics', function (Builder $query) {
                    $query->where('times_answered', '>', 10);
                })
                ->get()
                ->sortByDesc('statistics.success_rate')
                ->take(10),
            'needsReview' => (clone $baseQuery)
                ->with(['topic', 'statistics'])
                ->whereHas('statistics', function (Builder $query) {
                    $query->where('times_answered', '>', 10)
                        ->where('success_rate', '<', 40);
                })
                ->get(),
        ];
    }

    private function scopedQuery(?int $organizationId, bool $isNational, bool $withCreator = false): Builder
    {
        $relations = ['topic:id,name', 'statistics', 'choices'];
        if ($withCreator) {
            $relations[] = 'creator:id,name';
        }

        $query = QuestionBank::query()->with($relations);

        if ($isNational) {
            $query->national();
        } else {
            $query->institution()->forOrganization($organizationId);
        }

        return $query;
    }
}
