<?php

namespace App\Repositories\Contracts;

use App\Models\SubmissionFile;

interface SubmissionFileRepositoryInterface
{
    public function create(array $attributes): SubmissionFile;

    public function incrementDownloadCount(SubmissionFile $file): void;
}
