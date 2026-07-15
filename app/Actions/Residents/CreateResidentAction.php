<?php

namespace App\Actions\Residents;

use App\Models\Resident;
use App\Models\ResidentOrganizationMembership;
use App\Models\User;
use App\Repositories\Contracts\ResidentRepositoryInterface;

class CreateResidentAction
{
    public function __construct(
        private readonly ResidentRepositoryInterface $residentRepository,
    ) {}

    public function execute(array $validated): Resident
    {
        $residentData = collect($validated)
            ->except(['password', 'password_confirmation'])
            ->toArray();

        $resident = $this->residentRepository->create($residentData);

        $user = User::create([
            'name' => $resident->full_name,
            'email' => $validated['email'],
            'password' => $validated['password'],
            'current_organization_id' => $validated['organization_id'],
        ]);

        $this->residentRepository->update($resident, ['user_id' => $user->id]);

        $user->organizations()->attach($validated['organization_id'], [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        ResidentOrganizationMembership::create([
            'resident_id' => $resident->id,
            'organization_id' => $validated['organization_id'],
            'started_at' => now(),
            'ended_at' => null,
            'is_primary' => true,
            'year_level' => $resident->year_level,
            'status' => $resident->status,
        ]);

        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($validated['organization_id']);
        }

        $user->assignRole('Resident');

        return $resident->fresh(['user', 'organization']);
    }
}
