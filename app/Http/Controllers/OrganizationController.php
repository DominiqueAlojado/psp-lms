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

        // Get the referer URL
        $referer = $request->header('referer') ?? route('dashboard');
        $url = parse_url($referer);

        // Get the path, excluding any /organization/* routes
        $path = $url['path'] ?? '/dashboard';

        // If the path is an organization switch route, redirect to dashboard instead
        if (str_contains($path, '/organization/')) {
            $path = '/dashboard';
        }

        // Parse existing query parameters
        parse_str($url['query'] ?? '', $queryParams);

        // Add/update the org parameter
        $queryParams['org'] = $organization->slug;

        // Rebuild the URL with the org parameter
        $redirectUrl = $path.'?'.http_build_query($queryParams);

        return redirect($redirectUrl)->with('success', "Switched to {$organization->name}");
    }
}
