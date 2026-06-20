<?php

namespace App\Repositories\Contracts;

use App\Models\Assignment;
use App\Models\Event;
use App\Models\Submission;
use Illuminate\Support\Collection;

interface SubmissionRepositoryInterface
{
    public function getForAssignment(Assignment $assignment): Collection;

    public function create(array $attributes): Submission;

    public function update(Submission $submission, array $attributes): bool;
}
