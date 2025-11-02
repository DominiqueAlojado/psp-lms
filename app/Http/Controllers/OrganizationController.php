<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    /**
     * Switch the user's current organization.
     */
    public function switch(Request $request, Organization $organization): RedirectResponse
    {
        $user = $request->user();

        // Verify user belongs to this organization
        if (! $user->organizations->contains($organization->id)) {
            abort(403, 'You do not belong to this organization.');
        }

        // Switch to the organization
        $user->switchOrganization($organization);

        return back()->with('success', "Switched to {$organization->name}");
    }
}
