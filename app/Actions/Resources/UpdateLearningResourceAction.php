<?php

namespace App\Actions\Resources;

use App\Models\LearningResource;
use App\Repositories\Contracts\LearningResourceRepositoryInterface;

class UpdateLearningResourceAction
{
    public function __construct(
        private readonly LearningResourceRepositoryInterface $learningResourceRepository,
    ) {}

    public function execute(LearningResource $resource, array $attributes): bool
    {
        return $this->learningResourceRepository->update($resource, $attributes);
    }
}
