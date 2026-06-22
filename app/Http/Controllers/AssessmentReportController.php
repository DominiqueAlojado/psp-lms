<?php

namespace App\Http\Controllers;

use App\Services\AssessmentReportReadService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssessmentReportController extends Controller
{
    public function __construct(
        private readonly AssessmentReportReadService $assessmentReportReadService,
    ) {}

    /**
     * Display resident exam attempts with filtering.
     */
    public function byResident(Request $request): Response
    {
        return Inertia::render('assessment-reports/by-resident', $this->assessmentReportReadService->byResidentPayload($request));
    }

    /**
     * Live monitoring of active exam sessions.
     */
    public function liveMonitor(Request $request): Response
    {
        return Inertia::render('assessment-reports/live-monitor', $this->assessmentReportReadService->liveMonitorPayload($request));
    }
}
