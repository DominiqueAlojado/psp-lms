<?php

namespace App\Services;

use App\Models\Resident;
use App\Models\User;
use App\Repositories\Contracts\ResidentRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ResidentManagementService
{
    public function __construct(
        private readonly ResidentRepositoryInterface $residentRepository,
    ) {}

    public function create(array $validated): Resident
    {
        return DB::transaction(function () use ($validated) {
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

            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId($validated['organization_id']);
            }

            $user->assignRole('Resident');

            return $resident->fresh(['user', 'organization']);
        });
    }

    public function update(Resident $resident, array $validated): array
    {
        return DB::transaction(function () use ($resident, $validated) {
            $residentData = collect($validated)
                ->except(['password', 'password_confirmation'])
                ->toArray();

            $this->residentRepository->update($resident, $residentData);

            $passwordChanged = ! empty($validated['password']);

            if ($resident->user) {
                $userData = [
                    'name' => $resident->fresh()->full_name,
                    'email' => $validated['email'],
                ];

                if ($passwordChanged) {
                    $userData['password'] = $validated['password'];
                }

                $resident->user->update($userData);
            }

            return [
                'passwordChanged' => $passwordChanged,
            ];
        });
    }
}
