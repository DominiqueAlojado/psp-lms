<?php

namespace App\Repositories\Contracts;

use App\Models\Topic;
use Illuminate\Support\Collection;

interface TopicRepositoryInterface
{
    public function getForOrganizationWithGlobals(int $organizationId): Collection;

    public function findBySlugForOrganizationWithGlobals(string $slug, int $organizationId): ?Topic;

    public function create(array $attributes): Topic;
}
