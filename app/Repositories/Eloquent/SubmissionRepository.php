<?php

namespace App\Repositories\Eloquent;

use App\Models\Assignment;
use App\Models\Submission;
use App\Repositories\Contracts\SubmissionRepositoryInterface;
use Illuminate\Support\Collection;

class SubmissionRepository implements SubmissionRepositoryInterface
{
    public function getForAssignment(Assignment $assignment): Collection
    {
        return Submission::with(['user', 'files'])
            ->where('assignment_id', $assignment->id)
            ->whereIn('status', ['submitted', 'graded', 'returned'])
            ->orderBy('submitted_at', 'desc')
            ->get();
    }

    public function create(array $attributes): Submission
    {
        return Submission::create($attributes);
    }

    public function update(Submission $submission, array $attributes): bool
    {
        return $submission->update($attributes);
    }
}
