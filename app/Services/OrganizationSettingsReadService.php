<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use App\Repositories\Contracts\ResidentRepositoryInterface;

class OrganizationSettingsReadService
{
    public function __construct(
        private readonly ResidentRepositoryInterface $residentRepository,
    ) {}

    public function indexPayload(User $user): array
    {
        $organization = $this->authorizedOrganization($user, 'access');

        $residents = $this->residentRepository
            ->getForOrganization($organization->id)
            ->map(fn ($resident) => [
                'id' => $resident->id,
                'uuid' => $resident->uuid,
                'first_name' => $resident->first_name,
                'middle_name' => $resident->middle_name,
                'last_name' => $resident->last_name,
                'name' => $resident->full_name,
                'email' => $resident->email,
                'contact_number' => $resident->contact_number,
                'year_level' => $resident->year_level,
                'course' => $resident->course,
                'status' => $resident->status,
            ]);

        return [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'description' => $organization->description,
                'type' => $organization->type,
                'logo' => $organization->logo,
                'is_active' => $organization->is_active,
            ],
            'residents' => $residents,
        ];
    }

    public function authorizedOrganization(User $user, string $verb = 'manage'): Organization
    {
        if (! $user->hasPermissionTo('manage-organization-settings')) {
            $message = $verb === 'access'
                ? 'You do not have permission to access organization settings.'
                : 'You do not have permission to manage organization settings.';
            abort(403, $message);
        }

        $organization = $user->currentOrganization;

        if (! $organization) {
            abort(404, 'No current organization selected');
        }

        return $organization;
    }
}
