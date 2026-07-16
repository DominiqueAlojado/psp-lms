<?php

namespace App\Repositories\Eloquent;

use App\Models\Assignment;
use App\Models\User;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use Illuminate\Support\Collection;

class AssignmentRepository implements AssignmentRepositoryInterface
{
    public function getForOrganization(?int $organizationId, bool $includeAllOrganizations = false): Collection
    {
        return Assignment::with(['creator', 'submissions'])
            ->when($includeAllOrganizations, function ($query) {
                $query->whereNotNull('organization_id');
            })
            ->when(! $includeAllOrganizations, function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function create(array $attributes): Assignment
    {
        return Assignment::create($attributes);
    }

    public function update(Assignment $assignment, array $attributes): bool
    {
        return $assignment->update($attributes);
    }

    public function delete(Assignment $assignment): bool
    {
        return (bool) $assignment->delete();
    }

    public function getPublishedForResident(int $organizationId, ?string $yearLevel, User $user): Collection
    {
        return Assignment::where('organization_id', $organizationId)
            ->where('is_published', true)
            ->where(function ($query) use ($yearLevel) {
                $query->whereNull('target_year_levels')
                    ->orWhereJsonContains('target_year_levels', $yearLevel);
            })
            ->with(['submissions' => function ($query) use ($user) {
                $query->where('user_id', $user->id)->with('files');
            }])
            ->orderBy('due_date', 'asc')
            ->get();
    }
}
