<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Resident;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
            ->through(fn ($resident) => [
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

        return Inertia::render('residents/index', [
            'residents' => $residents,
            'organizations' => $organizations,
            'filters' => $request->only(['search', 'organization_id', 'year_level', 'status', 'course']),
            'yearLevels' => ['Pre Resident', 'First Year', 'Second Year', 'Third Year', 'Fourth Year', 'Graduate'],
            'statuses' => ['active', 'inactive'],
            'courses' => Resident::distinct()->pluck('course')->filter()->values(),
        ]);
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
}
