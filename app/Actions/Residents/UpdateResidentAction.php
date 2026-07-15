<?php

namespace App\Actions\Residents;

use App\Models\Resident;
use App\Repositories\Contracts\ResidentRepositoryInterface;

class UpdateResidentAction
{
    public function __construct(
        private readonly ResidentRepositoryInterface $residentRepository,
    ) {}

    public function execute(Resident $resident, array $validated): array
    {
        $residentData = collect($validated)
            ->except(['password', 'password_confirmation', 'organization_id'])
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
    }
}
