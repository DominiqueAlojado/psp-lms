<?php

namespace App\Http\Controllers;

use App\Exports\InstitutionsExport;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrganizationController extends Controller
{
    /**
     * Display a listing of institutions.
     */
    public function index(Request $request): Response
    {
        $institutions = Organization::query()
            ->withCount(['residents', 'users'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%");
                });
            })
            ->when($request->input('type'), function ($query, $type) {
                $query->where('type', $type);
            })
            ->when($request->input('status'), function ($query, $status) {
                $isActive = $status === 'active';
                $query->where('is_active', $isActive);
            })
            ->orderBy($request->input('sort', 'name'), $request->input('direction', 'asc'))
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($institution) => [
                'id' => $institution->id,
                'name' => $institution->name,
                'slug' => $institution->slug,
                'description' => $institution->description,
                'type' => $institution->type,
                'is_active' => $institution->is_active,
                'residents_count' => $institution->residents_count,
                'users_count' => $institution->users_count,
                'training_officers_count' => $institution->training_officers_count,
                'updated_at' => $institution->updated_at->diffForHumans(),
            ]);

        // Get statistics per type
        $typeStats = Organization::query()
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        // Get unique types
        $types = Organization::distinct()->pluck('type')->filter()->values();

        return Inertia::render('institutions/index', [
            'institutions' => $institutions,
            'filters' => $request->only(['search', 'type', 'status']),
            'types' => $types,
            'typeStats' => $typeStats,
        ]);
    }

    /**
     * Store a newly created institution.
     */
    public function store(Request $request): RedirectResponse
    {
        \Log::info('=== STORE METHOD CALLED ===');
        \Log::info('Request data', $request->all());

        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255', 'unique:organizations,name'],
                'description' => ['nullable', 'string'],
                'type' => ['required', 'string', 'in:chapter,institution,main,national'],
                'is_active' => ['boolean'],
            ], [
                'name.required' => 'Institution name is required',
                'name.unique' => 'An institution with this name already exists',
                'type.required' => 'Institution type is required',
                'type.in' => 'Please select a valid institution type',
            ]);

            \Log::info('Validation passed', $validated);

            // Generate slug from name
            $validated['slug'] = Str::slug($validated['name']);
            $validated['is_active'] = $validated['is_active'] ?? true;

            \Log::info('Creating institution with data:', $validated);

            $institution = Organization::create($validated);

            \Log::info('SUCCESS! Institution created:', [
                'id' => $institution->id,
                'name' => $institution->name,
            ]);

            return back()->with('success', 'Institution created successfully');
        } catch (\Exception $e) {
            \Log::error('ERROR creating institution: '.$e->getMessage());
            \Log::error('Stack trace: '.$e->getTraceAsString());

            return back()->withErrors(['error' => 'Failed to create institution: '.$e->getMessage()]);
        }
    }

    /**
     * Update the specified institution.
     */
    public function update(Request $request, Organization $organization): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:organizations,name,'.$organization->id],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:chapter,institution,main,national'],
            'is_active' => ['boolean'],
        ], [
            'name.required' => 'Institution name is required',
            'name.unique' => 'An institution with this name already exists',
            'type.required' => 'Institution type is required',
            'type.in' => 'Please select a valid institution type',
        ]);

        // Update slug if name changed
        if ($validated['name'] !== $organization->name) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $organization->update($validated);

        return back()->with('success', 'Institution updated successfully');
    }

    /**
     * Remove the specified institution.
     */
    public function destroy(Organization $organization): RedirectResponse
    {
        // Check if institution has residents
        if ($organization->residents()->count() > 0) {
            return back()->withErrors([
                'error' => 'Cannot delete institution with active residents. Please reassign or delete residents first.',
            ]);
        }

        $organization->delete();

        return back()->with('success', 'Institution deleted successfully');
    }

    /**
     * Export institutions to Excel.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $filters = $request->only(['search', 'type', 'status']);

        $filename = 'institutions_'.now()->format('Y-m-d_His').'.xlsx';

        return Excel::download(new InstitutionsExport($filters), $filename);
    }

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
