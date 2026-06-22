<?php

namespace App\Actions\Assignments;

use App\Models\Submission;
use App\Repositories\Contracts\SubmissionRepositoryInterface;

class UpdateSubmissionAction
{
    public function __construct(
        private readonly SubmissionRepositoryInterface $submissionRepository,
    ) {}

    public function execute(Submission $submission, array $attributes): bool
    {
        return $this->submissionRepository->update($submission, $attributes);
    }
}
