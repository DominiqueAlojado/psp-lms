<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Services\GradebookReadService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GradebookController extends Controller
{
    public function __construct(
        private readonly GradebookReadService $gradebookReadService,
    ) {}

    /**
     * Display the resident's personal performance dashboard.
     */
    public function myGrades(Request $request): Response
    {
        return Inertia::render('gradebook/my-grades', $this->gradebookReadService->myGradesPayload($request->user()));
    }

    /**
     * Display gradebook for faculty/training officers (all residents).
     */
    public function index(Request $request): Response
    {
        return Inertia::render('assessment-reports/by-performance', $this->gradebookReadService->indexPayload($request->user()));
    }

    /**
     * Display detailed performance report for a specific resident.
     */
    public function show(Request $request, Resident $resident): Response
    {
        return Inertia::render('assessment-reports/resident-detail', $this->gradebookReadService->showPayload($request->user(), $resident));
    }
}
