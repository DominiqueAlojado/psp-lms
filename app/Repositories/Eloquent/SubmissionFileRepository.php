<?php

namespace App\Repositories\Eloquent;

use App\Models\SubmissionFile;
use App\Repositories\Contracts\SubmissionFileRepositoryInterface;

class SubmissionFileRepository implements SubmissionFileRepositoryInterface
{
    public function create(array $attributes): SubmissionFile
    {
        return SubmissionFile::create($attributes);
    }

    public function incrementDownloadCount(SubmissionFile $file): void
    {
        $file->incrementDownloadCount();
    }
}
