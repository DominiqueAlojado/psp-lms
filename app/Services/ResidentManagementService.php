<?php

namespace App\Services;

use App\Actions\Residents\AttachResidentOrganizationAction;
use App\Actions\Residents\CreateResidentAction;
use App\Actions\Residents\DeleteResidentAction;
use App\Actions\Residents\DetachResidentOrganizationAction;
use App\Actions\Residents\UpdateResidentAction;
use App\Models\Organization;
use App\Models\Resident;
use App\Repositories\Contracts\ResidentRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ResidentManagementService
{
    public function __construct(
        private readonly ResidentRepositoryInterface $residentRepository,
        private readonly CreateResidentAction $createResidentAction,
        private readonly UpdateResidentAction $updateResidentAction,
        private readonly DeleteResidentAction $deleteResidentAction,
        private readonly AttachResidentOrganizationAction $attachResidentOrganizationAction,
        private readonly DetachResidentOrganizationAction $detachResidentOrganizationAction,
    ) {}

    public function create(array $validated): Resident
    {
        return DB::transaction(fn () => $this->createResidentAction->execute($validated));
    }

    public function update(Resident $resident, array $validated): array
    {
        return DB::transaction(fn () => $this->updateResidentAction->execute($resident, $validated));
    }

    public function delete(Resident $resident): bool
    {
        return $this->deleteResidentAction->execute($resident);
    }

    public function attachOrganization(Resident $resident, Organization $organization): bool
    {
        return $this->attachResidentOrganizationAction->execute($resident, $organization);
    }

    public function detachOrganization(Resident $resident, Organization $organization): bool
    {
        return $this->detachResidentOrganizationAction->execute($resident, $organization);
    }
}
