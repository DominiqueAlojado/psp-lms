<?php

namespace App\Actions\Resources;

use App\Models\LearningResource;
use App\Repositories\Contracts\LearningResourceRepositoryInterface;

class DeleteLearningResourceAction
{
    public function __construct(
        private readonly LearningResourceRepositoryInterface $learningResourceRepository,
    ) {}

    public function execute(LearningResource $resource): bool
    {
        return $this->learningResourceRepository->delete($resource);
    }
}
