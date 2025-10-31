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

        return redirect()
            ->route('admin.tenants.index')
            ->with('success', "Tenant '{$tenant->name}' created successfully. Add domain '{$tenant->domain}' to Coolify.");
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

        return Inertia::render('admin/tenants/show', [
            'tenant' => $tenant,
            'localSetupStatus' => $setupStatus,
            'success' => session('success'),
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
                    'message' => 'Setup failed: '.$e->getMessage(),
                    'error' => $e->getMessage(),
                ], 500);
            }

            return redirect()
                ->route('admin.tenants.show', $tenant)
                ->withErrors(['setup' => 'Setup failed: '.$e->getMessage()]);
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
