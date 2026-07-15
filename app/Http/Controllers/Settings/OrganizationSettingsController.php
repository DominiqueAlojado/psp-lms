<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateOrganizationResidentRequest;
use App\Http\Requests\Settings\UpdateOrganizationSettingsRequest;
use App\Http\Requests\Settings\UploadOrganizationLogoRequest;
use App\Services\OrganizationSettingsManagementService;
use App\Services\OrganizationSettingsReadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationSettingsController extends Controller
{
    public function __construct(
        private readonly OrganizationSettingsReadService $readService,
        private readonly OrganizationSettingsManagementService $managementService,
    ) {}

    /**
     * Display the organization settings page.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('settings/organization', $this->readService->indexPayload($request->user()));
    }

    /**
     * Update the organization details.
     */
    public function update(UpdateOrganizationSettingsRequest $request): RedirectResponse
    {
        $this->managementService->updateOrganization($request->user(), $request->validated());

        return back()->with('success', 'Organization updated successfully');
    }

    /**
     * Upload organization logo.
     */
    public function uploadLogo(UploadOrganizationLogoRequest $request): RedirectResponse
    {
        $this->managementService->uploadLogo($request->user(), $request->file('logo'));

        return back()->with('success', 'Logo updated successfully');
    }

    /**
     * Delete organization logo.
     */
    public function deleteLogo(Request $request): RedirectResponse
    {
        $this->managementService->deleteLogo($request->user());

        return back()->with('success', 'Logo deleted successfully');
    }

    /**
     * Update a resident's information.
     */
    public function updateResident(UpdateOrganizationResidentRequest $request, $residentId): RedirectResponse
    {
        $this->managementService->updateResident($request->user(), (int) $residentId, $request->validated());

        return back()->with('success', 'Resident updated successfully');
    }
}
