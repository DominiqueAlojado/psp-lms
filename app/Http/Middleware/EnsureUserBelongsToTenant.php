<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserBelongsToTenant
{
    /**
     * Handle an incoming request.
     *
     * Ensures that the authenticated user belongs to the current tenant.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $currentTenant = Tenant::current();

        if (! $user || ! $currentTenant) {
            abort(403, 'Unauthorized.');
        }

        if (! $user->belongsToCurrentTenant()) {
            abort(403, 'You do not have access to this hospital.');
        }

        return $next($request);
    }
}
