<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSystemAdmin
{
    /**
     * Handle an incoming request.
     *
     * Only allow system admins (users with global 'system admin' role).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isSystemAdmin()) {
            abort(403, 'This action requires system administrator access.');
        }

        return $next($request);
    }
}
