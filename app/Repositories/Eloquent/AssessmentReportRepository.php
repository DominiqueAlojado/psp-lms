<?php

namespace App\Repositories\Eloquent;

use App\Models\ExamIdlePeriod;
use App\Models\ExamSessionChange;
use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionAttempt;
use App\Models\National\NationalAssessment;
use App\Models\National\NationalAttempt;
use App\Models\Organization;
use App\Repositories\Contracts\AssessmentReportRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

class AssessmentReportRepository implements AssessmentReportRepositoryInterface
{
    public function institutionExamExists(int $examId): bool
    {
        return InstitutionAssessment::query()->where('id', $examId)->exists();
    }

    public function nationalExamExists(int $examId): bool
    {
        return NationalAssessment::query()->where('id', $examId)->exists();
    }

    public function getCompletedInstitutionAttemptsForReport(array $filters, ?int $organizationId, bool $canViewAllOrganizations): Collection
    {
        $query = InstitutionAttempt::query()
            ->with([
                'user:id,name,email',
                'assessment:id,title,total_points,passing_score,exam_category',
                'organization:id,name',
            ])
            ->where('status', 'completed');

        if (! $canViewAllOrganizations && $organizationId) {
            $query->where('organization_id', $organizationId);
        }

        $this->applyCommonAttemptFilters($query, $filters, 'institution_assessments');

        if (($filters['exam_type'] ?? null) === 'institution' && ! empty($filters['exam_id'])) {
            $query->where('assessment_id', $filters['exam_id']);
        }

        return $query->get();
    }

    public function getCompletedNationalAttemptsForReport(array $filters, ?int $organizationId, bool $canViewAllOrganizations): Collection
    {
        $query = NationalAttempt::query()
            ->with([
                'user:id,name,email',
                'assessment:id,title,total_points,passing_score,category',
                'organization:id,name',
            ])
            ->whereIn('status', ['completed', 'graded']);

        if (! $canViewAllOrganizations && $organizationId) {
            $query->where('organization_id', $organizationId);
        }

        $this->applyCommonAttemptFilters($query, $filters, 'national_assessments');

        if (($filters['exam_type'] ?? null) === 'national' && ! empty($filters['exam_id'])) {
            $query->where('assessment_id', $filters['exam_id']);
        }

        return $query->get();
    }

    public function getOrganizations(): Collection
    {
        return Organization::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function getPublishedInstitutionExamOptions(?int $organizationId, bool $canViewAllOrganizations): Collection
    {
        return InstitutionAssessment::query()
            ->when(! $canViewAllOrganizations && $organizationId, function (Builder $query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })
            ->where('is_published', true)
            ->orderBy('title')
            ->get(['id', 'title']);
    }

    public function getPublishedNationalExamOptions(): Collection
    {
        return NationalAssessment::query()
            ->where('is_published', true)
            ->orderBy('title')
            ->get(['id', 'title']);
    }

    public function getLiveInstitutionAttempts(array $filters, ?int $organizationId, bool $canViewAllOrganizations): Collection
    {
        $query = InstitutionAttempt::query()
            ->with([
                'user:id,name,email',
                'assessment:id,title,exam_category',
                'organization:id,name',
            ])
            ->where('status', 'in_progress');

        if (! $canViewAllOrganizations && $organizationId) {
            $query->where('organization_id', $organizationId);
        }

        if (! empty($filters['exam'])) {
            $query->where('assessment_id', $filters['exam']);
        }

        if (! empty($filters['organization']) && $canViewAllOrganizations) {
            $query->where('organization_id', $filters['organization']);
        }

        if (! empty($filters['activity_status'])) {
            if ($filters['activity_status'] === 'idle') {
                $query->where('last_activity_at', '<', now()->subMinutes(2));
            } elseif ($filters['activity_status'] === 'suspicious') {
                $query->where(function (Builder $builder) {
                    $builder->where('ip_changes_count', '>', 0)
                        ->orWhere('browser_changes_count', '>', 0);
                });
            } elseif ($filters['activity_status'] === 'active') {
                $query->where('last_activity_at', '>=', now()->subMinutes(2));
            }
        }

        return $query->orderBy('started_at', 'desc')->get();
    }

    public function getSessionChangesForInstitutionAttempt(int $attemptId): Collection
    {
        return ExamSessionChange::query()
            ->where('attempt_type', 'institution')
            ->where('attempt_id', $attemptId)
            ->orderBy('detected_at', 'asc')
            ->get();
    }

    public function getIdlePeriodsForInstitutionAttempt(int $attemptId): Collection
    {
        return ExamIdlePeriod::query()
            ->where('attempt_type', 'institution')
            ->where('attempt_id', $attemptId)
            ->orderBy('started_at', 'asc')
            ->get();
    }

    public function getActiveWebSessionsForUsers(array $userIds): Collection
    {
        $userIds = array_values(array_filter(array_unique(array_map('intval', $userIds))));
        $table = (string) config('session.table', 'sessions');

        if ($userIds === [] || ! Schema::hasTable($table)) {
            return collect();
        }

        return DB::table($table)
            ->select(['id', 'user_id', 'ip_address', 'user_agent', 'last_activity'])
            ->whereNotNull('user_id')
            ->whereIn('user_id', $userIds)
            ->orderByDesc('last_activity')
            ->get()
            ->groupBy('user_id')
            ->map(fn (Collection $sessions) => $sessions->values());
    }

    private function applyCommonAttemptFilters(Builder $query, array $filters, string $assessmentTable): void
    {
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function (Builder $userQuery) use ($search) {
                $userQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['organization']) && ! empty($filters['can_view_all_organizations'])) {
            $query->where('organization_id', $filters['organization']);
        }

        if (! empty($filters['year_level'])) {
            $query->where('year_level', $filters['year_level']);
        }

        if (! empty($filters['status'])) {
            if ($filters['status'] === 'passed') {
                $query->whereRaw("score >= (SELECT passing_score FROM {$assessmentTable} WHERE id = assessment_id)");
            } elseif ($filters['status'] === 'failed') {
                $query->whereRaw("score < (SELECT passing_score FROM {$assessmentTable} WHERE id = assessment_id)");
            }
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('submitted_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('submitted_at', '<=', $filters['date_to']);
        }
    }
}
