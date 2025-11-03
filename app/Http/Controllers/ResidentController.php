<?php

namespace App\Http\Controllers;

use App\Exports\ResidentsExport;
use App\Models\Organization;
use App\Models\Resident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ResidentController extends Controller
{
    /**
     * Display a listing of residents with search and filters.
     */
    public function index(Request $request): Response
    {
        $currentOrg = auth()->user()->currentOrganization;

        $residents = Resident::query()
            ->with(['organization', 'user'])
            ->when($request->input('search'), function ($query, $search) {
                $query->search($search);
            })
            ->when($request->input('organization_id'), function ($query, $orgId) {
                $query->where('organization_id', $orgId);
            })
            ->when($request->input('year_level'), function ($query, $yearLevel) {
                $query->where('year_level', $yearLevel);
            })
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->input('course'), function ($query, $course) {
                $query->where('course', $course);
            })
            ->orderBy($request->input('sort', 'last_name'), $request->input('direction', 'asc'))
            ->paginate(15)
            ->withQueryString()
            ->through(fn($resident) => [
                'id' => $resident->id,
                'uuid' => $resident->uuid,
                'full_name' => $resident->full_name,
                'full_name_with_middle_initial' => $resident->full_name_with_middle_initial,
                'first_name' => $resident->first_name,
                'middle_name' => $resident->middle_name,
                'last_name' => $resident->last_name,
                'email' => $resident->email,
                'contact_number' => $resident->contact_number,
                'course' => $resident->course,
                'year_level' => $resident->year_level,
                'status' => $resident->status,
                'organization' => [
                    'id' => $resident->organization->id,
                    'name' => $resident->organization->name,
                    'slug' => $resident->organization->slug,
                ],
            ]);

        $organizations = Organization::query()
            ->select('id', 'name', 'slug')
            ->orderBy('name')
            ->get();

        // Get statistics per year level
        $yearLevelStats = Resident::query()
            ->selectRaw('year_level, COUNT(*) as count')
            ->groupBy('year_level')
            ->pluck('count', 'year_level')
            ->toArray();

        return Inertia::render('residents/index', [
            'residents' => $residents,
            'organizations' => $organizations,
            'filters' => $request->only(['search', 'organization_id', 'year_level', 'status', 'course']),
            'yearLevels' => ['Pre Resident', 'First Year', 'Second Year', 'Third Year', 'Fourth Year', 'Graduate'],
            'statuses' => ['active', 'inactive'],
            'courses' => Resident::distinct()->pluck('course')->filter()->values(),
            'yearLevelStats' => $yearLevelStats,
        ]);
    }

    /**
     * Store a newly created resident.
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'organization_id' => ['required', 'exists:organizations,id'],
                'first_name' => ['required', 'string', 'max:255'],
                'middle_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email:rfc', 'max:255', 'unique:residents,email'],
                'contact_number' => ['required', 'string', 'regex:/^(\+63|0)?9\d{9}$/'],
                'course' => ['required', 'string', 'max:255'],
                'year_level' => ['required', 'string', 'in:Pre Resident,First Year,Second Year,Third Year,Fourth Year,Graduate'],
                'status' => ['required', 'string', 'in:active,inactive'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ], [
                'organization_id.required' => 'Organization is required',
                'first_name.required' => 'First name is required',
                'middle_name.required' => 'Middle name is required',
                'last_name.required' => 'Last name is required',
                'email.email' => 'Please enter a valid email address.',
                'email.required' => 'Please enter a valid email address.',
                'contact_number.required' => 'Contact number is required',
                'contact_number.regex' => 'Contact number must be a valid Philippine mobile number (e.g., 09123456789 or +639123456789).',
                'course.required' => 'Course is required',
                'year_level.required' => 'Year level is required',
                'password.required' => 'Password is required',
                'password.min' => 'Password must be at least 8 characters',
                'password.confirmed' => "Passwords don't match",
            ]);

            // Create the resident (exclude password fields)
            $residentData = collect($validated)->except(['password', 'password_confirmation'])->toArray();
            $resident = Resident::create($residentData);

            // Create a user account for the resident
            $user = \App\Models\User::create([
                'name' => $resident->full_name,
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
            ]);

            // Link the user to the resident
            $resident->update(['user_id' => $user->id]);

            // Attach user to organization
            $user->organizations()->attach($validated['organization_id'], [
                'joined_at' => now(),
                'is_active' => true,
            ]);

            // Assign "Resident" role to the user
            $user->assignRole('Resident');

            return back()->with('success', 'Resident created successfully');
        } catch (\Exception $e) {
            \Log::error('Error creating resident: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => 'Failed to create resident: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resident.
     */
    public function show(Resident $resident): Response
    {
        $resident->load(['organization', 'user']);

        return Inertia::render('residents/show', [
            'resident' => [
                'id' => $resident->id,
                'uuid' => $resident->uuid,
                'full_name' => $resident->full_name,
                'full_name_with_middle_initial' => $resident->full_name_with_middle_initial,
                'first_name' => $resident->first_name,
                'middle_name' => $resident->middle_name,
                'last_name' => $resident->last_name,
                'email' => $resident->email,
                'contact_number' => $resident->contact_number,
                'course' => $resident->course,
                'year_level' => $resident->year_level,
                'status' => $resident->status,
                'other_info' => $resident->other_info,
                'created_at' => $resident->created_at,
                'organization' => [
                    'id' => $resident->organization->id,
                    'name' => $resident->organization->name,
                    'slug' => $resident->organization->slug,
                ],
            ],
        ]);
    }

    /**
     * Update the specified resident.
     */
    public function update(Request $request, Resident $resident): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:residents,email,' . $resident->id],
            'contact_number' => ['required', 'string', 'regex:/^(\+63|0)?9\d{9}$/'],
            'course' => ['required', 'string', 'max:255'],
            'year_level' => ['required', 'string', 'in:Pre Resident,First Year,Second Year,Third Year,Fourth Year,Graduate'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'first_name.required' => 'First name is required',
            'middle_name.required' => 'Middle name is required',
            'last_name.required' => 'Last name is required',
            'email.email' => 'Please enter a valid email address.',
            'email.required' => 'Please enter a valid email address.',
            'contact_number.required' => 'Contact number is required',
            'contact_number.regex' => 'Contact number must be a valid Philippine mobile number (e.g., 09123456789 or +639123456789).',
            'course.required' => 'Course is required',
            'year_level.required' => 'Year level is required',
            'password.min' => 'Password must be at least 8 characters',
            'password.confirmed' => "Passwords don't match",
        ]);

        $resident->update($validated);

        // Update linked user if exists
        if ($resident->user) {
            $resident->user->update([
                'name' => $resident->full_name,
                'email' => $validated['email'],
            ]);

            // Update password if provided
            if (! empty($validated['password'])) {
                $resident->user->update([
                    'password' => bcrypt($validated['password']),
                ]);
            }
        }

        return back()->with('success', 'Resident updated successfully');
    }

    /**
     * Remove the specified resident.
     */
    public function destroy(Resident $resident): RedirectResponse
    {
        $resident->delete();

        return back()->with('success', 'Resident deleted successfully');
    }

    /**
     * Export residents to Excel.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $filters = $request->only(['search', 'organization_id', 'year_level', 'status', 'course']);

        $filename = 'residents_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new ResidentsExport($filters), $filename);
    }
}
