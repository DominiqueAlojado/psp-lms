<?php

namespace App\Http\Controllers;

use App\Services\ResidentExamManagementService;
use App\Services\ResidentExamReadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResidentExamController extends Controller
{
    private const EXAM_SESSION_CONFLICT_MESSAGE = 'This exam is already active in another browser or device.';

    public function __construct(
        private readonly ResidentExamManagementService $residentExamManagementService,
        private readonly ResidentExamReadService $residentExamReadService,
    ) {}

    /**
     * Show the exam taking page (start or resume).
     */
    public function take(Request $request, string $type, int $id): Response
    {
        return Inertia::render('resident-exams/take', $this->residentExamReadService->takePayload($request, $type, $id));
    }

    /**
     * Save a single answer (auto-save as resident answers).
     */
    public function saveAnswer(Request $request, string $type, int $attempt): JsonResponse
    {
        $response = $this->residentExamManagementService->saveAnswer($request, $type, $attempt);

        return response()->json(
            isset($response['error']) ? ['error' => $response['error']] : ['success' => true],
            $response['status']
        );
    }

    /**
     * Finalize exam submission (calculate score and mark as completed).
     */
    public function submit(Request $request, string $type, int $attempt): RedirectResponse
    {
        $response = $this->residentExamManagementService->submit($request, $type, $attempt);

        if (isset($response['error'])) {
            return back()->withErrors(['error' => $response['error']]);
        }

        return redirect('/resident-exams')->with('success', $response['message']);
    }

    /**
     * Display exam results for a specific exam.
     */
    public function results(Request $request, string $type, int $id): Response
    {
        try {
            return Inertia::render('resident-exams/results', $this->residentExamReadService->resultsPagePayload($request->user(), $type, $id));
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            if ($e->getStatusCode() === 302) {
                return redirect('/resident-exams')->with('error', 'No completed attempts found for this exam.');
            }

            throw $e;
        }
    }

    /**
     * Get exam results as JSON for API calls.
     */
    public function resultsApi(Request $request, string $type, int $id): JsonResponse
    {
        return response()->json($this->residentExamReadService->resultsApiPayload($request->user(), $type, $id));
    }

    /**
     * Display exams available to the resident.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('resident-exams/index', $this->residentExamReadService->indexPayload($request->user()));
    }

    /**
     * Log browser or IP change during exam session.
     */
    public function logSessionChange(Request $request, string $type, int $attemptId)
    {
        $this->residentExamManagementService->logSessionChange($request, $type, $attemptId);

        return response()->noContent();
    }

    /**
     * Log activity and idle time for an exam attempt.
     */
    public function logActivity(Request $request, string $type, int $attemptId)
    {
        $this->residentExamManagementService->logActivity($request, $type, $attemptId);

        return response()->noContent();
    }

    /**
     * Get current IP address for change detection.
     */
    public function getCurrentIp(Request $request, string $type, int $attemptId)
    {
        return response()->json($this->residentExamReadService->currentIpPayload($request, $type, $attemptId));
    }

    /**
     * Get session info for browser change detection.
     */
    public function getSessionInfo(Request $request, string $type, int $attemptId)
    {
        return response()->json($this->residentExamReadService->sessionInfoPayload($request, $type, $attemptId));
    }

    /**
     * Update exam attempt metadata (called from frontend after page load).
     */
    public function updateMetadata(Request $request, string $type, int $attemptId)
    {
        return response()->json($this->residentExamManagementService->updateMetadata($request, $type, $attemptId));
    }
}
