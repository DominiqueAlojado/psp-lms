<?php

namespace App\Services;

use App\Actions\Organizations\CreateOrganizationAction;
use App\Actions\Organizations\DeleteOrganizationAction;
use App\Actions\Organizations\SwitchUserOrganizationAction;
use App\Actions\Organizations\UpdateOrganizationAction;
use App\Models\Organization;
use App\Models\User;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use Illuminate\Support\Str;

class OrganizationManagementService
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizationRepository,
        private readonly CreateOrganizationAction $createOrganizationAction,
        private readonly UpdateOrganizationAction $updateOrganizationAction,
        private readonly DeleteOrganizationAction $deleteOrganizationAction,
        private readonly SwitchUserOrganizationAction $switchUserOrganizationAction,
    ) {}

    public function create(array $validated): Organization
    {
        return $this->createOrganizationAction->execute(
            $this->prepareAttributes($validated)
        );
    }

    public function update(Organization $organization, array $validated): array
    {
        $attributes = $this->prepareAttributes($validated, $organization);

        $this->updateOrganizationAction->execute($organization, $attributes);

        return $attributes;
    }

    public function delete(Organization $organization): bool
    {
        return $this->deleteOrganizationAction->execute($organization);
    }

    public function hasResidents(Organization $organization): bool
    {
        return $this->organizationRepository->hasResidents($organization);
    }

    public function switchUserOrganization(User $user, Organization $organization): bool
    {
        return $this->switchUserOrganizationAction->execute($user, $organization);
    }

    private function prepareAttributes(array $validated, ?Organization $organization = null): array
    {
        $validated['slug'] = isset($organization) && $validated['name'] === $organization->name
            ? $organization->slug
            : Str::slug($validated['name']);

        $validated['is_active'] = $validated['is_active'] ?? true;

        if (isset($validated['training_officers']) && is_string($validated['training_officers'])) {
            $validated['training_officers'] = json_decode($validated['training_officers'], true) ?? [];
        }

        return $validated;
    }
}
