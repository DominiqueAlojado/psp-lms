<?php

namespace App\Actions\Assignments;

use App\Models\Assignment;
use App\Repositories\Contracts\AssignmentRepositoryInterface;

class DeleteAssignmentAction
{
    public function __construct(
        private readonly AssignmentRepositoryInterface $assignmentRepository,
    ) {}

    public function execute(Assignment $assignment): bool
    {
        return $this->assignmentRepository->delete($assignment);
    }
}
