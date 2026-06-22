<?php

namespace App\Actions\Assignments;

use App\Models\SubmissionFile;
use App\Repositories\Contracts\SubmissionFileRepositoryInterface;

class CreateSubmissionFileAction
{
    public function __construct(
        private readonly SubmissionFileRepositoryInterface $submissionFileRepository,
    ) {}

    public function execute(array $attributes): SubmissionFile
    {
        return $this->submissionFileRepository->create($attributes);
    }
}
