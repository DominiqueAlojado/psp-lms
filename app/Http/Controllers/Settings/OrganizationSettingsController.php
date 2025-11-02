<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationSettingsController extends Controller
{
    /**
     * Display the organization settings page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $organization = $user->currentOrganization;

        if (! $organization) {
            abort(404, 'No current organization selected');
        }

        // Load residents for this organization
        $residents = $organization->residents()
            ->with('user')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn ($resident) => [
                'id' => $resident->id,
                'uuid' => $resident->uuid,
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
        $organization = $user->currentOrganization;

        if (! $organization) {
            abort(404, 'No current organization selected');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $organization->update($validated);

        return back()->with('success', 'Organization updated successfully');
    }

    /**
     * Upload organization logo.
     */
    public function uploadLogo(Request $request): RedirectResponse
    {
        $user = $request->user();
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

        $organization->update(['logo' => $path]);

        return back()->with('success', 'Logo updated successfully');
    }

    /**
     * Delete organization logo.
     */
    public function deleteLogo(Request $request): RedirectResponse
    {
        $user = $request->user();
        $organization = $user->currentOrganization;

        if (! $organization) {
            abort(404, 'No current organization selected');
        }

        if ($organization->logo) {
            Storage::disk('public')->delete($organization->logo);
            $organization->update(['logo' => null]);
        }

        return back()->with('success', 'Logo deleted successfully');
    }
}
