<?php

namespace App\Actions\Assignments;

use App\Models\Assignment;
use App\Repositories\Contracts\AssignmentRepositoryInterface;

class UpdateAssignmentAction
{
    public function __construct(
        private readonly AssignmentRepositoryInterface $assignmentRepository,
    ) {}

    public function execute(Assignment $assignment, array $attributes): bool
    {
        return $this->assignmentRepository->update($assignment, $attributes);
    }
}
