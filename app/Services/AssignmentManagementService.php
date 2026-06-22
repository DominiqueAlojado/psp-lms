<?php

namespace App\Services;

use App\Actions\Assignments\CreateAssignmentAction;
use App\Actions\Assignments\DeleteAssignmentAction;
use App\Actions\Assignments\UpdateAssignmentAction;
use App\Models\Assignment;
use App\Models\User;

class AssignmentManagementService
{
    public function __construct(
        private readonly CreateAssignmentAction $createAssignmentAction,
        private readonly UpdateAssignmentAction $updateAssignmentAction,
        private readonly DeleteAssignmentAction $deleteAssignmentAction,
    ) {}

    public function create(User $user, array $validated): Assignment
    {
        return $this->createAssignmentAction->execute([
            ...$validated,
            'organization_id' => $user->currentOrganization?->id,
            'created_by' => $user->id,
        ]);
    }

    public function update(Assignment $assignment, array $validated): bool
    {
        return $this->updateAssignmentAction->execute($assignment, $validated);
    }

    public function delete(Assignment $assignment): bool
    {
        return $this->deleteAssignmentAction->execute($assignment);
    }

    public function canCreateFromCurrentOrganization(User $user): bool
    {
        return $user->currentOrganization !== null && $user->currentOrganization->type !== 'national';
    }
}
