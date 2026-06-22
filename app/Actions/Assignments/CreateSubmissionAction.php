<?php

namespace App\Actions\Assignments;

use App\Models\Submission;
use App\Repositories\Contracts\SubmissionRepositoryInterface;

class CreateSubmissionAction
{
    public function __construct(
        private readonly SubmissionRepositoryInterface $submissionRepository,
    ) {}

    public function execute(array $attributes): Submission
    {
        return $this->submissionRepository->create($attributes);
    }
}
