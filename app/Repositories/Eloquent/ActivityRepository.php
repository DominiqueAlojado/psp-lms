<?php

namespace App\Repositories\Eloquent;

use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\Event;
use App\Models\Institution\InstitutionAssessment;
use App\Models\LearningResource;
use App\Models\National\NationalAssessment;
use App\Models\Organization;
use App\Models\QuestionBank;
use App\Models\Resident;
use App\Models\User;
use App\Repositories\Contracts\ActivityRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class ActivityRepository implements ActivityRepositoryInterface
{
    public function paginateForOrganization(int $organizationId, ?string $organizationType, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->baseQuery($organizationId, $organizationType, $filters);

        $activities = $query
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $this->loadRelations($activities);

        return $activities;
    }

    public function getSummaryForOrganization(int $organizationId, ?string $organizationType, array $filters): array
    {
        $query = $this->baseQuery($organizationId, $organizationType, $filters);

        $todayQuery = clone $query;
        $actorQuery = clone $query;
        $moduleQuery = clone $query;

        return [
            'total' => $query->count(),
            'today' => $todayQuery->whereDate('created_at', today())->count(),
            'actors' => $actorQuery
                ->where('causer_type', User::class)
                ->distinct('causer_id')
                ->count('causer_id'),
            'modules' => $moduleQuery
                ->whereNotNull('log_name')
                ->distinct('log_name')
                ->count('log_name'),
        ];
    }

    private function baseQuery(int $organizationId, ?string $organizationType, array $filters): Builder
    {
        return Activity::query()
            ->with(['causer', 'subject'])
            ->where(function (Builder $query) use ($organizationId, $organizationType) {
                $this->applyOrganizationScope($query, $organizationId, $organizationType);
            })
            ->when($filters['module'] ?? null, function (Builder $query, string $module) {
                $query->where('log_name', $module);
            })
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $nestedQuery) use ($search) {
                    $nestedQuery->where('description', 'like', "%{$search}%")
                        ->orWhereHasMorph('causer', [User::class], function (Builder $causerQuery) use ($search) {
                            $causerQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['date_from'] ?? null, function (Builder $query, string $dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($filters['date_to'] ?? null, function (Builder $query, string $dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            });
    }

    private function applyOrganizationScope(Builder $query, int $organizationId, ?string $organizationType): void
    {
        $query
            ->whereHasMorph('subject', [Announcement::class], function (Builder $subjectQuery) use ($organizationId) {
                $subjectQuery->visibleTo($organizationId);
            })
            ->orWhereHasMorph('subject', [LearningResource::class], function (Builder $subjectQuery) use ($organizationId) {
                $subjectQuery->where(function (Builder $nestedQuery) use ($organizationId) {
                    $nestedQuery->where(function (Builder $organizationQuery) use ($organizationId) {
                        $organizationQuery->where('scope', 'organization')
                            ->where('organization_id', $organizationId);
                    })->orWhere('scope', 'system');
                });
            })
            ->orWhereHasMorph('subject', [Event::class], function (Builder $subjectQuery) use ($organizationId) {
                $subjectQuery->forOrganization($organizationId);
            })
            ->orWhereHasMorph('subject', [Assignment::class], function (Builder $subjectQuery) use ($organizationId) {
                $subjectQuery->where('organization_id', $organizationId);
            })
            ->orWhereHasMorph('subject', [Resident::class], function (Builder $subjectQuery) use ($organizationId) {
                $subjectQuery->where('organization_id', $organizationId);
            })
            ->orWhereHasMorph('subject', [Organization::class], function (Builder $subjectQuery) use ($organizationId) {
                $subjectQuery->whereKey($organizationId);
            })
            ->orWhereHasMorph('subject', [User::class], function (Builder $subjectQuery) use ($organizationId) {
                $subjectQuery->where('current_organization_id', $organizationId);
            })
            ->orWhereHasMorph('subject', [QuestionBank::class], function (Builder $subjectQuery) use ($organizationId) {
                $subjectQuery->where(function (Builder $nestedQuery) use ($organizationId) {
                    $nestedQuery->where('organization_id', $organizationId)
                        ->orWhereNull('organization_id');
                });
            })
            ->orWhereHasMorph('subject', [InstitutionAssessment::class], function (Builder $subjectQuery) use ($organizationId) {
                $subjectQuery->where('organization_id', $organizationId);
            });

        if ($organizationType === 'national') {
            $query->orWhereHasMorph('subject', [NationalAssessment::class], function (Builder $subjectQuery) {
                $subjectQuery->whereNotNull('id');
            });
        }
    }

    private function loadRelations(LengthAwarePaginator $activities): void
    {
        $activities->getCollection()->loadMorph('subject', [
            Announcement::class => ['organization'],
            Assignment::class => ['organization'],
            Event::class => ['organization'],
            InstitutionAssessment::class => ['organization'],
            LearningResource::class => ['organization'],
            Organization::class => [],
            QuestionBank::class => ['organization'],
            Resident::class => ['organization'],
            User::class => ['currentOrganization'],
            NationalAssessment::class => [],
        ]);

        $activities->getCollection()->loadMorph('causer', [
            User::class => ['currentOrganization'],
        ]);
    }
}
