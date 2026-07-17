<?php

namespace App\Http\Middleware;

use App\Services\NotificationReadService;
use App\Services\SystemConfigReadService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    private const ALL_ORGANIZATIONS_SLUG = 'all-organizations';

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user = $request->user();
        $supportsAllOrganizations = $user?->hasAnyRole(['System Admin', 'BOP']) ?? false;
        $isAllOrganizationsContext = $supportsAllOrganizations
            && $request->query('org') === self::ALL_ORGANIZATIONS_SLUG;
        $currentOrganization = $isAllOrganizationsContext
            ? (object) [
                'id' => 0,
                'name' => 'All Organizations',
                'slug' => self::ALL_ORGANIZATIONS_SLUG,
                'type' => 'all',
                'logo' => null,
            ]
            : $user?->currentOrganization;

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $user,
                'organizations' => $user ? $user->organizations()
                    ->wherePivot('organization_user.is_active', true)
                    ->get(['organizations.id', 'organizations.name', 'organizations.slug', 'organizations.type', 'organizations.logo'])
                    : null,
                'currentOrganization' => $currentOrganization,
                'actualOrganization' => $user?->currentOrganization,
                'supportsAllOrganizations' => $supportsAllOrganizations,
                'permissions' => $user?->getAllPermissions()->pluck('name')->toArray() ?? [],
                'roles' => $user?->getRoleNames()->toArray() ?? [],
            ],
            'notifications' => $user
                ? app(NotificationReadService::class)->sharedPayload($user)
                : [
                    'unreadCount' => 0,
                    'latest' => [],
                ],
            'appConfig' => app(SystemConfigReadService::class)->publicPayload(),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
                'info' => session('info'),
                'warning' => session('warning'),
                'assessment_id' => session('assessment_id'),
            ],
        ];
    }

    /**
     * Set the root template that's loaded on the first page visit.
     */
    public function rootView(Request $request): string
    {
        return parent::rootView($request);
    }

    /**
     * Handle Inertia responses and add CSRF token to headers.
     */
    public function handle(Request $request, \Closure $next): \Symfony\Component\HttpFoundation\Response
    {
        $response = parent::handle($request, $next);

        // Add CSRF token to response headers for Inertia requests
        // This allows the frontend to update the CSRF token after each request
        if ($request->header('X-Inertia')) {
            $response->headers->set('X-CSRF-Token', csrf_token());
        }

        return $response;
    }
}
