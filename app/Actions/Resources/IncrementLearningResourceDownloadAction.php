<?php

namespace App\Actions\Resources;

use App\Models\LearningResource;
use App\Repositories\Contracts\LearningResourceRepositoryInterface;

class IncrementLearningResourceDownloadAction
{
    public function __construct(
        private readonly LearningResourceRepositoryInterface $learningResourceRepository,
    ) {}

    public function execute(LearningResource $resource): void
    {
        $this->learningResourceRepository->incrementDownloadCount($resource);
    }
}
