<?php

namespace App\Actions\Assignments;

use App\Models\Assignment;
use App\Repositories\Contracts\AssignmentRepositoryInterface;

class CreateAssignmentAction
{
    public function __construct(
        private readonly AssignmentRepositoryInterface $assignmentRepository,
    ) {}

    public function execute(array $attributes): Assignment
    {
        return $this->assignmentRepository->create($attributes);
    }
}
