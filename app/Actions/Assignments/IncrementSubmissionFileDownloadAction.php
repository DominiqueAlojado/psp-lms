<?php

namespace App\Actions\Assignments;

use App\Models\SubmissionFile;
use App\Repositories\Contracts\SubmissionFileRepositoryInterface;

class IncrementSubmissionFileDownloadAction
{
    public function __construct(
        private readonly SubmissionFileRepositoryInterface $submissionFileRepository,
    ) {}

    public function execute(SubmissionFile $file): void
    {
        $this->submissionFileRepository->incrementDownloadCount($file);
    }
}
