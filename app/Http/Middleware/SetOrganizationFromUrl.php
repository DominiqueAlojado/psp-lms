<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetOrganizationFromUrl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Skip organization switch routes, logout, settings, residents, institutions, staff, resources, announcements, and exam form submissions
        if (
            $request->is('organization/*/switch')
            || $request->is('logout')
            || ($request->is('settings/*') && in_array($request->method(), ['POST', 'PATCH', 'PUT', 'DELETE']))
            || ($request->is('residents*') && in_array($request->method(), ['POST', 'PATCH', 'PUT', 'DELETE']))
            || ($request->is('institutions*') && in_array($request->method(), ['POST', 'PATCH', 'PUT', 'DELETE']))
            || ($request->is('staff*') && in_array($request->method(), ['POST', 'PATCH', 'PUT', 'DELETE']))
            || ($request->is('resources*') && in_array($request->method(), ['POST', 'PATCH', 'PUT', 'DELETE']))
            || ($request->is('announcements*') && in_array($request->method(), ['POST', 'PATCH', 'PUT', 'DELETE']))
            || ($request->is('assessments*') && in_array($request->method(), ['POST', 'PATCH', 'PUT', 'DELETE']))
            || ($request->is('institution-exams*') && in_array($request->method(), ['POST', 'PATCH', 'PUT', 'DELETE']))
            || ($request->is('in-service*') && in_array($request->method(), ['POST', 'PATCH', 'PUT', 'DELETE']))
            || ($request->is('inservice-exams*') && in_array($request->method(), ['POST', 'PATCH', 'PUT', 'DELETE']))
            || ($request->is('topics*') && in_array($request->method(), ['POST', 'PATCH', 'PUT', 'DELETE']))
            || ($request->is('exams/*') && in_array($request->method(), ['POST', 'PATCH', 'PUT', 'DELETE']))
        ) {
            return $next($request);
        }

        $orgSlug = $request->query('org');

        // If org parameter is in URL
        if ($orgSlug) {
            // Find organization by slug
            $organization = Organization::where('slug', $orgSlug)->first();

            // If organization exists and user belongs to it
            if ($organization && $user->organizations->contains($organization->id)) {
                // Switch if different from current
                if ($user->current_organization_id !== $organization->id) {
                    $user->switchOrganization($organization);
                }
            }
        } elseif ($user->current_organization_id) {
            // No org in URL but user has current org - redirect to add it
            $currentOrg = $user->currentOrganization;
            if ($currentOrg) {
                $queryParams = $request->query();
                $queryParams['org'] = $currentOrg->slug;

                return redirect($request->path().'?'.http_build_query($queryParams));
            }
        }

        // Set permission team context for the current organization
        if ($user->current_organization_id) {
            setPermissionsTeamId($user->current_organization_id);
        }

        return $next($request);
    }
}
