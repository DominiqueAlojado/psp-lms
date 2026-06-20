<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateOrganizationResidentRequest;
use App\Http\Requests\Settings\UpdateOrganizationSettingsRequest;
use App\Http\Requests\Settings\UploadOrganizationLogoRequest;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use App\Repositories\Contracts\ResidentRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationSettingsController extends Controller
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizationRepository,
        private readonly ResidentRepositoryInterface $residentRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * Display the organization settings page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Check permission
        if (! $user->hasPermissionTo('manage-organization-settings')) {
            abort(403, 'You do not have permission to access organization settings.');
        }

        $organization = $user->currentOrganization;

        if (! $organization) {
            abort(404, 'No current organization selected');
        }

        // Load residents for this organization
        $residents = $this->residentRepository
            ->getForOrganization($organization->id)
            ->map(fn($resident) => [
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

        return Inertia::render('settings/organization', [
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
        ]);
    }

    /**
     * Update the organization details.
     */
    public function update(UpdateOrganizationSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Check permission
        if (! $user->hasPermissionTo('manage-organization-settings')) {
            abort(403, 'You do not have permission to manage organization settings.');
        }

        $organization = $user->currentOrganization;

        if (! $organization) {
            abort(404, 'No current organization selected');
        }

        $validated = $request->validated();

        $this->organizationRepository->update($organization, $validated);

        return back()->with('success', 'Organization updated successfully');
    }

    /**
     * Upload organization logo.
     */
    public function uploadLogo(UploadOrganizationLogoRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Check permission
        if (! $user->hasPermissionTo('manage-organization-settings')) {
            abort(403, 'You do not have permission to manage organization settings.');
        }

        $organization = $user->currentOrganization;

        if (! $organization) {
            abort(404, 'No current organization selected');
        }

        // Delete old logo if exists
        if ($organization->logo) {
            Storage::disk('public')->delete($organization->logo);
        }

        // Store new logo
        $path = $request->file('logo')->store('organization-logos', 'public');

        $this->organizationRepository->update($organization, ['logo' => $path]);

        return back()->with('success', 'Logo updated successfully');
    }

    /**
     * Delete organization logo.
     */
    public function deleteLogo(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Check permission
        if (! $user->hasPermissionTo('manage-organization-settings')) {
            abort(403, 'You do not have permission to manage organization settings.');
        }

        $organization = $user->currentOrganization;

        if (! $organization) {
            abort(404, 'No current organization selected');
        }

        if ($organization->logo) {
            Storage::disk('public')->delete($organization->logo);
            $this->organizationRepository->update($organization, ['logo' => null]);
        }

        return back()->with('success', 'Logo deleted successfully');
    }

    /**
     * Update a resident's information.
     */
    public function updateResident(UpdateOrganizationResidentRequest $request, $residentId): RedirectResponse
    {
        $user = $request->user();

        // Check permission
        if (! $user->hasPermissionTo('manage-organization-settings')) {
            abort(403, 'You do not have permission to manage organization settings.');
        }

        $organization = $user->currentOrganization;

        if (! $organization) {
            abort(404, 'No current organization selected');
        }

        // Find resident and verify they belong to current organization
        $resident = $this->residentRepository->findForOrganization($organization->id, (int) $residentId);

        $validated = $request->validated();

        $residentData = $validated;
        unset($residentData['password']);

        $this->residentRepository->update($resident, $residentData);

        // Update user account if linked
        if ($resident->user) {
            $userUpdate = [
                'email' => $validated['email'],
                'name' => $resident->full_name,
            ];

            // Update password if provided
            if (! empty($validated['password'])) {
                $userUpdate['password'] = $validated['password'];
            }

            $this->userRepository->update($resident->user, $userUpdate);
        }

        return back()->with('success', 'Resident updated successfully');
    }
}
