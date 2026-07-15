<?php

namespace App\Services;

use App\Repositories\Contracts\TopicRepositoryInterface;
use App\Models\Topic;

class TopicManagementService
{
    public function __construct(
        private readonly TopicRepositoryInterface $topicRepository,
    ) {}

    public function createForOrganization(int $organizationId, array $validated): Topic
    {
        return $this->topicRepository->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'organization_id' => $organizationId,
            'is_global' => false,
        ]);
    }
}
