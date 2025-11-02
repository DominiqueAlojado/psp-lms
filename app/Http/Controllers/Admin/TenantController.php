<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTenantRequest;
use App\Http\Requests\Admin\UpdateTenantRequest;
use App\Models\Tenant;
use App\Services\TenantSetupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    /**
     * Display a listing of tenants.
     */
    public function index(Request $request): Response
    {
        $tenants = Tenant::query()
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/tenants/index', [
            'tenants' => $tenants,
        ]);
    }

    /**
     * Show the form for creating a new tenant.
     */
    public function create(): Response
    {
        return Inertia::render('admin/tenants/create');
    }

    /**
     * Store a newly created tenant.
     */
    public function store(StoreTenantRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Generate database name if not provided
        if (empty($data['database'])) {
            $data['database'] = $this->generateDatabaseName($data['domain']);
        }

        $tenant = Tenant::create($data);

        // Create default roles for the tenant
        $tenant->createDefaultRoles();

        $message = "Tenant '{$tenant->name}' created successfully!";

        // Only attempt local setup on Windows local development
        // In production (Coolify/Linux), domains are managed via DNS/Coolify
        if (config('app.env') === 'local' && PHP_OS_FAMILY === 'Windows') {
            // Automatically attempt local development setup (hosts file)
            $setupService = new TenantSetupService;

            // Try to add to hosts file (may fail without admin access - that's OK)
            try {
                $hostsResult = $setupService->addToHostsFile($tenant->domain);

                if ($hostsResult === true) {
                    $message .= ' ✅ Domain added to hosts file automatically. Just restart Apache in Laragon.';
                } elseif ($hostsResult === 'exists') {
                    $message .= ' ✅ Domain already in hosts file. Ready to use!';
                } else {
                    // Hosts file addition failed - show friendly message
                    return redirect()
                        ->route('admin.tenants.show', $tenant)
                        ->with('success', $message)
                        ->with('info', '⚠️ Domain needs to be added to hosts file. Click "Setup Local Environment" below (requires admin access).')
                        ->with('setupNeeded', true);
                }
            } catch (\Exception $e) {
                // If setup fails silently, just show tenant created message
                return redirect()
                    ->route('admin.tenants.show', $tenant)
                    ->with('success', $message)
                    ->with('info', '⚠️ Click "Setup Local Environment" to add domain to hosts file (requires admin access).')
                    ->with('setupNeeded', true);
            }
        } else {
            // Production: Add domain setup instructions for Coolify
            $message .= ' Add domain "' . $tenant->domain . '" to Coolify with SSL enabled.';
        }

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('success', $message);
    }

    /**
     * Display the specified tenant.
     */
    public function show(Tenant $tenant): Response
    {
        $tenant->loadCount('residents');

        // Check local setup status
        $setupService = new TenantSetupService;
        $setupStatus = $setupService->checkLocalSetupStatus($tenant->domain);

        // Only show local setup options on Windows local development
        $isLocalWindows = config('app.env') === 'local' && PHP_OS_FAMILY === 'Windows';

        return Inertia::render('admin/tenants/show', [
            'tenant' => $tenant,
            'localSetupStatus' => $setupStatus,
            'isLocalWindows' => $isLocalWindows,
            'success' => session('success'),
            'info' => session('info'),
            'setupNeeded' => session('setupNeeded', false),
            'errors' => session('errors'),
        ]);
    }

    /**
     * Show the form for editing the specified tenant.
     */
    public function edit(Tenant $tenant): Response
    {
        return Inertia::render('admin/tenants/edit', [
            'tenant' => $tenant,
        ]);
    }

    /**
     * Update the specified tenant.
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validated();

        // Generate database name if not provided
        if (empty($data['database'])) {
            $data['database'] = $this->generateDatabaseName($data['domain']);
        }

        $tenant->update($data);

        return redirect()
            ->route('admin.tenants.index')
            ->with('success', "Tenant '{$tenant->name}' updated successfully.");
    }

    /**
     * Remove the specified tenant.
     */
    public function destroy(Tenant $tenant): RedirectResponse
    {
        $name = $tenant->name;
        $tenant->delete();

        return redirect()
            ->route('admin.tenants.index')
            ->with('success', "Tenant '{$name}' deleted successfully.");
    }

    /**
     * Setup local development environment for a tenant.
     */
    public function setupLocalDevelopment(Tenant $tenant): RedirectResponse|JsonResponse
    {
        try {
            $setupService = new TenantSetupService;
            $results = $setupService->setupLocalDevelopment($tenant->domain);

            // Check if this is an Inertia request (not a regular JSON request)
            $isInertiaRequest = request()->header('X-Inertia');

            if (! $isInertiaRequest && request()->wantsJson()) {
                // Regular JSON request (e.g., from API or fetch)
                return response()->json([
                    'success' => true,
                    'message' => $results['message'],
                    'status' => [
                        'hosts' => $results['hosts'],
                        'virtual_host' => $results['virtual_host'],
                        'apache_restart' => $results['apache_restart'] ?? false,
                    ],
                    'needs_restart' => ($results['apache_restart'] ?? false) !== true,
                ]);
            }

            // For Inertia requests, redirect back with success message
            return redirect()
                ->route('admin.tenants.show', $tenant)
                ->with('success', $results['message']);
        } catch (\Exception $e) {
            $isInertiaRequest = request()->header('X-Inertia');

            if (! $isInertiaRequest && request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Setup failed: ' . $e->getMessage(),
                    'error' => $e->getMessage(),
                ], 500);
            }

            return redirect()
                ->route('admin.tenants.show', $tenant)
                ->withErrors(['setup' => 'Setup failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Generate a database name from domain.
     */
    private function generateDatabaseName(string $domain): string
    {
        $name = str_replace('.', '_', $domain);
        $name = preg_replace('/[^a-z0-9_]/', '', strtolower($name));

        return $name;
    }
}
