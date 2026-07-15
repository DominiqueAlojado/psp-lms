<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use App\Repositories\Contracts\ResidentRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class OrganizationSettingsManagementService
{
    public function __construct(
        private readonly OrganizationSettingsReadService $readService,
        private readonly OrganizationRepositoryInterface $organizationRepository,
        private readonly ResidentRepositoryInterface $residentRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function updateOrganization(User $user, array $validated): void
    {
        $organization = $this->readService->authorizedOrganization($user);
        $this->organizationRepository->update($organization, $validated);
    }

    public function uploadLogo(User $user, UploadedFile $logo): void
    {
        $organization = $this->readService->authorizedOrganization($user);

        if ($organization->logo) {
            Storage::disk('public')->delete($organization->logo);
        }

        $path = $logo->store('organization-logos', 'public');
        $this->organizationRepository->update($organization, ['logo' => $path]);
    }

    public function deleteLogo(User $user): void
    {
        $organization = $this->readService->authorizedOrganization($user);

        if (! $organization->logo) {
            return;
        }

        Storage::disk('public')->delete($organization->logo);
        $this->organizationRepository->update($organization, ['logo' => null]);
    }

    public function updateResident(User $user, int $residentId, array $validated): void
    {
        $organization = $this->readService->authorizedOrganization($user);
        $resident = $this->residentRepository->findForOrganization($organization->id, $residentId);

        $residentData = $validated;
        unset($residentData['password']);

        $this->residentRepository->update($resident, $residentData);

        if (! $resident->user) {
            return;
        }

        $userUpdate = [
            'email' => $validated['email'],
            'name' => $resident->fresh()->full_name,
        ];

        if (! empty($validated['password'])) {
            $userUpdate['password'] = $validated['password'];
        }

        $this->userRepository->update($resident->user, $userUpdate);
    }
}
