<?php

namespace App\Repositories\Eloquent;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionAttempt;
use App\Models\National\NationalAssessment;
use App\Models\National\NationalAttempt;
use App\Models\Organization;
use App\Models\QuestionBank;
use App\Models\QuestionBankStatistic;
use App\Models\Topic;
use App\Repositories\Contracts\AnalyticsRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AnalyticsRepository implements AnalyticsRepositoryInterface
{
    public function getPublishedInstitutionExams(?int $organizationId, bool $canViewAllOrganizations): Collection
    {
        return InstitutionAssessment::query()
            ->select('id', 'title', 'exam_category', 'total_points', 'passing_score')
            ->when(! $canViewAllOrganizations && $organizationId, function (Builder $query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })
            ->where('is_published', true)
            ->orderBy('title')
            ->get();
    }

    public function getPublishedNationalExams(): Collection
    {
        return NationalAssessment::query()
            ->select('id', 'title', 'category', 'total_points', 'passing_score')
            ->where('is_published', true)
            ->orderBy('title')
            ->get();
    }

    public function getActiveInstitutionOrganizations(): Collection
    {
        return Organization::query()
            ->where('type', 'institution')
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function findInstitutionAssessmentForAnalytics(int $examId, bool $withChoices = false): InstitutionAssessment
    {
        $relations = $withChoices ? ['questions.choices', 'questions.topic'] : ['questions.topic'];

        return InstitutionAssessment::with($relations)->findOrFail($examId);
    }

    public function findNationalAssessmentForAnalytics(int $examId, bool $withChoices = false): NationalAssessment
    {
        $relations = $withChoices ? ['questions.choices'] : ['questions'];

        return NationalAssessment::with($relations)->findOrFail($examId);
    }

    public function getCompletedInstitutionAttemptsForExam(int $examId, ?int $organizationId, bool $canViewAllOrganizations, array $filters, bool $withAnswers = false): Collection
    {
        $query = InstitutionAttempt::query()
            ->where('assessment_id', $examId)
            ->where('status', 'completed');

        if ($withAnswers) {
            $query->with(['answers', 'user', 'assessment']);
        } else {
            $query->with('assessment');
        }

        if (! $canViewAllOrganizations && $organizationId) {
            $query->where('organization_id', $organizationId);
        }

        if (! empty($filters['organization'])) {
            $query->where('organization_id', $filters['organization']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('submitted_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('submitted_at', '<=', $filters['date_to']);
        }

        return $query->get();
    }

    public function getCompletedNationalAttemptsForExam(int $examId, array $filters, bool $withAnswers = false): Collection
    {
        $query = NationalAttempt::query()
            ->where('assessment_id', $examId)
            ->where('status', 'completed');

        if ($withAnswers) {
            $query->with(['answers', 'user', 'assessment']);
        } else {
            $query->with('assessment');
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('submitted_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('submitted_at', '<=', $filters['date_to']);
        }

        return $query->get();
    }

    public function paginateQuestionBankAnalytics(?int $organizationId, bool $isNational, array $filters, string $sortBy, string $sortOrder, int $perPage = 20): LengthAwarePaginator
    {
        $query = QuestionBank::query()
            ->with([
                'topic:id,name',
                'choices',
                'creator:id,name',
            ])
            ->with(['allStatistics' => function ($statisticsQuery) use ($isNational, $organizationId) {
                $statisticsQuery->where('scope', $isNational ? 'national' : 'institution');
                if (! $isNational) {
                    $statisticsQuery->where('institution_id', $organizationId);
                } else {
                    $statisticsQuery->whereNull('institution_id');
                }
            }])
            ->withCount('assessments');

        if ($isNational) {
            $query->national();
        } else {
            $query->institution()->forOrganization($organizationId);
        }

        if (! empty($filters['search'])) {
            $query->where('question_text', 'like', '%' . $filters['search'] . '%');
        }

        if (! empty($filters['topic'])) {
            $query->where('topic_id', $filters['topic']);
        }

        if (! empty($filters['question_type'])) {
            $query->where('question_type', $filters['question_type']);
        }

        if (! empty($filters['difficulty'])) {
            if ($filters['difficulty'] === 'computed') {
                $query->whereHas('statistics', function (Builder $statisticsQuery) use ($isNational, $organizationId) {
                    $statisticsQuery->where('scope', $isNational ? 'national' : 'institution')
                        ->whereNotNull('computed_difficulty');
                    if (! $isNational) {
                        $statisticsQuery->where('institution_id', $organizationId);
                    }
                });
            } else {
                $query->where('difficulty_level', $filters['difficulty']);
            }
        }

        if (! empty($filters['approval_status'])) {
            if ($filters['approval_status'] === 'approved') {
                $query->where('is_approved', true);
            } elseif ($filters['approval_status'] === 'pending') {
                $query->where('is_approved', false);
            }
        }

        if (! empty($filters['performance_filter'])) {
            $perfFilter = $filters['performance_filter'];
            $query->whereHas('statistics', function (Builder $statisticsQuery) use ($perfFilter, $isNational, $organizationId) {
                $statisticsQuery->where('scope', $isNational ? 'national' : 'institution');
                if (! $isNational) {
                    $statisticsQuery->where('institution_id', $organizationId);
                }
                if ($perfFilter === 'needs_review') {
                    $statisticsQuery->where(function (Builder $subQuery) {
                        $subQuery->where('discrimination_index', '<', 0.1)
                            ->orWhere('skip_count', '>', 10)
                            ->orWhere('success_rate', '<', 30);
                    });
                } elseif ($perfFilter === 'excellent') {
                    $statisticsQuery->where('discrimination_index', '>=', 0.3)
                        ->where('success_rate', '>=', 40)
                        ->where('success_rate', '<=', 70);
                } elseif ($perfFilter === 'never_used') {
                    $statisticsQuery->where('times_answered', 0);
                }
            });
        }

        if (in_array($sortBy, ['success_rate', 'discrimination_index', 'times_answered'])) {
            $query->leftJoin('question_bank_statistics', function ($join) {
                $join->on('question_bank.id', '=', 'question_bank_statistics.question_id');
            })
                ->select('question_bank.*')
                ->orderBy("question_bank_statistics.{$sortBy}", $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function getQuestionBankSummary(?int $organizationId, bool $isNational): array
    {
        $summaryQuery = QuestionBank::query();
        if ($isNational) {
            $summaryQuery->national();
        } else {
            $summaryQuery->institution()->forOrganization($organizationId);
        }

        $totalQuestions = $summaryQuery->count();
        $approvedQuestions = (clone $summaryQuery)->where('is_approved', true)->count();

        $avgSuccessRate = QuestionBankStatistic::query()
            ->where('scope', $isNational ? 'national' : 'institution')
            ->when(! $isNational, function (Builder $query) use ($organizationId) {
                $query->where('institution_id', $organizationId);
            })
            ->whereHas('question', function (Builder $questionQuery) use ($isNational, $organizationId) {
                if ($isNational) {
                    $questionQuery->national();
                } else {
                    $questionQuery->institution()->forOrganization($organizationId);
                }
            })
            ->where('times_answered', '>', 0)
            ->avg('success_rate');

        return [
            'total_questions' => $totalQuestions,
            'approved_questions' => $approvedQuestions,
            'pending_questions' => $totalQuestions - $approvedQuestions,
            'average_success_rate' => round($avgSuccessRate ?? 0, 2),
        ];
    }

    public function getQuestionBankTopics(?int $organizationId, bool $isNational): Collection
    {
        $topicIds = QuestionBank::query()
            ->when($isNational, fn (Builder $query) => $query->national(), fn (Builder $query) => $query->institution()->forOrganization($organizationId))
            ->whereNotNull('topic_id')
            ->distinct()
            ->pluck('topic_id');

        return Topic::query()
            ->whereIn('id', $topicIds)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }
}
