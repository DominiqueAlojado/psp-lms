<?php

namespace App\Repositories\Eloquent;

use App\Models\Topic;
use App\Repositories\Contracts\TopicRepositoryInterface;
use Illuminate\Support\Collection;

class TopicRepository implements TopicRepositoryInterface
{
    public function getForOrganizationWithGlobals(int $organizationId): Collection
    {
        return Topic::query()
            ->where(function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId)
                    ->orWhere('is_global', true);
            })
            ->orderBy('is_global', 'desc')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_global']);
    }

    public function findBySlugForOrganizationWithGlobals(string $slug, int $organizationId): ?Topic
    {
        return Topic::query()
            ->where('slug', $slug)
            ->where(function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId)
                    ->orWhere('is_global', true);
            })
            ->first();
    }

    public function create(array $attributes): Topic
    {
        return Topic::create($attributes);
    }
}
