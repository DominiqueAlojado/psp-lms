<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
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
        $residents = $this->organizationRepository
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
    public function update(Request $request): RedirectResponse
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

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $this->organizationRepository->update($organization, $validated);

        return back()->with('success', 'Organization updated successfully');
    }

    /**
     * Upload organization logo.
     */
    public function uploadLogo(Request $request): RedirectResponse
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

        $request->validate([
            'logo' => ['required', 'image', 'max:2048'], // 2MB max
        ]);

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
    public function updateResident(Request $request, $residentId): RedirectResponse
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

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:residents,email,' . $resident->id],
            'contact_number' => ['required', 'string', 'regex:/^(\+63|0)?9\d{9}$/'],
            'course' => ['required', 'string', 'max:255'],
            'year_level' => ['required', 'string', 'in:Pre Resident,First Year,Second Year,Third Year,Fourth Year,Graduate'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'email.email' => 'Please enter a valid email address.',
            'contact_number.regex' => 'Contact number must be a valid Philippine mobile number (e.g., 09123456789 or +639123456789).',
        ]);

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
