<?php

namespace App\Services;

use App\Repositories\Contracts\TopicRepositoryInterface;
use Illuminate\Support\Collection;

class TopicReadService
{
    public function __construct(
        private readonly TopicRepositoryInterface $topicRepository,
    ) {}

    public function listForOrganization(int $organizationId): Collection
    {
        return $this->topicRepository->getForOrganizationWithGlobals($organizationId);
    }
}
