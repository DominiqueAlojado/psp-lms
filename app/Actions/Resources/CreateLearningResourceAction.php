<?php

namespace App\Actions\Resources;

use App\Models\LearningResource;
use App\Repositories\Contracts\LearningResourceRepositoryInterface;

class CreateLearningResourceAction
{
    public function __construct(
        private readonly LearningResourceRepositoryInterface $learningResourceRepository,
    ) {}

    public function execute(array $attributes): LearningResource
    {
        return $this->learningResourceRepository->create($attributes);
    }
}
