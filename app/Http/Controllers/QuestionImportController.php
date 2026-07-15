<?php

namespace App\Http\Controllers;

use App\Models\Institution\InstitutionAssessment;
use App\Services\InstitutionAssessmentImportService;
use App\Services\InstitutionAssessmentQuestionPreviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QuestionImportController extends Controller
{
    public function __construct(
        private readonly InstitutionAssessmentQuestionPreviewService $previewService,
        private readonly InstitutionAssessmentImportService $importService,
    ) {}

    /**
     * Preview questions from uploaded Excel file.
     */
    public function preview(Request $request, InstitutionAssessment $assessment): JsonResponse
    {
        if ($assessment->organization_id !== $request->user()->current_organization_id) {
            abort(403, 'You do not have access to this assessment.');
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        try {
            return response()->json($this->previewService->preview($assessment, $request->file('file')));
        } catch (\Throwable $e) {
            Log::error('Question preview failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to parse file: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Confirm and import questions after preview.
     */
    public function confirmImport(Request $request, InstitutionAssessment $assessment): RedirectResponse
    {
        if ($assessment->organization_id !== $request->user()->current_organization_id) {
            abort(403, 'You do not have access to this assessment.');
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        try {
            $result = $this->importService->importQuestions(
                $assessment,
                $request->file('file'),
                $request->user(),
            );

            return back()->with($result['status'], $result['message']);
        } catch (\Throwable $e) {
            return back()->withErrors($this->importService->formatImportFailure($e));
        }
    }
}
