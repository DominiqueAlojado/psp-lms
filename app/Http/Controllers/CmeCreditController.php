<?php

namespace App\Http\Controllers;

use App\Models\CmeCredit;
use App\Services\CmeCreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CmeCreditController extends Controller
{
    public function __construct(
        protected CmeCreditService $cmeCreditService
    ) {}

    /**
     * Display the CME/CPD credits dashboard.
     */
    public function index(Request $request): Response
    {
        // Check if CME/CPD tracking is enabled
        if (! config('cme_cpd.enabled', true)) {
            abort(404, 'CME/CPD tracking is not enabled.');
        }

        $user = $request->user();

        // Get total credits (all time) - combined
        $totalCredits = CmeCredit::getTotalCreditsForUser($user->id);

        // Get separate CME and CPD totals (all time)
        $totalCmeCredits = $this->cmeCreditService->getTotalCmeCredits($user->id);
        $totalCpdCredits = $this->cmeCreditService->getTotalCpdCredits($user->id);

        // Get credits for current year
        $currentYearStart = now()->startOfYear();
        $currentYearEnd = now()->endOfYear();
        $currentYearCredits = CmeCredit::getTotalCreditsForUser(
            $user->id,
            $currentYearStart->toDateString(),
            $currentYearEnd->toDateString()
        );

        // Get CME and CPD credits for current year
        $currentYearCmeCredits = $this->cmeCreditService->getTotalCmeCredits(
            $user->id,
            $currentYearStart->toDateString(),
            $currentYearEnd->toDateString()
        );
        $currentYearCpdCredits = $this->cmeCreditService->getTotalCpdCredits(
            $user->id,
            $currentYearStart->toDateString(),
            $currentYearEnd->toDateString()
        );

        // Get credits by source type
        $creditsBySource = CmeCredit::where('user_id', $user->id)
            ->where('status', 'approved')
            ->selectRaw('source_type, SUM(credits) as total')
            ->groupBy('source_type')
            ->get()
            ->pluck('total', 'source_type')
            ->toArray();

        // Get credits by credit type
        $creditsByType = CmeCredit::where('user_id', $user->id)
            ->where('status', 'approved')
            ->selectRaw('credit_type, SUM(credits) as total')
            ->groupBy('credit_type')
            ->get()
            ->pluck('total', 'credit_type')
            ->toArray();

        // Get recent credits (last 10)
        $recentCredits = CmeCredit::where('user_id', $user->id)
            ->where('status', 'approved')
            ->orderBy('earned_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($credit) {
                return [
                    'id' => $credit->id,
                    'credits' => $credit->credits,
                    'description' => $credit->description,
                    'source_type' => $credit->source_type,
                    'credit_type' => $credit->credit_type ?? 'cme',
                    'earned_at' => $credit->earned_at->format('M d, Y'),
                    'source' => $this->getSourceName($credit),
                ];
            });

        return Inertia::render('cme-credits/index', [
            'totalCredits' => $totalCredits,
            'totalCmeCredits' => $totalCmeCredits,
            'totalCpdCredits' => $totalCpdCredits,
            'currentYearCredits' => $currentYearCredits,
            'currentYearCmeCredits' => $currentYearCmeCredits,
            'currentYearCpdCredits' => $currentYearCpdCredits,
            'creditsBySource' => $creditsBySource,
            'creditsByType' => $creditsByType,
            'recentCredits' => $recentCredits,
        ]);
    }

    /**
     * Get credit history with pagination.
     */
    public function history(Request $request): Response
    {
        // Check if CME/CPD tracking is enabled
        if (! config('cme_cpd.enabled', true)) {
            abort(404, 'CME/CPD tracking is not enabled.');
        }

        $user = $request->user();
        $perPage = $request->input('per_page', 20);

        $credits = $this->cmeCreditService->getCreditHistory($user->id, $perPage);

        $credits->getCollection()->transform(function ($credit) {
            return [
                'id' => $credit->id,
                'credits' => $credit->credits,
                'description' => $credit->description,
                'source_type' => $credit->source_type,
                'credit_type' => $credit->credit_type ?? 'cme',
                'earned_at' => $credit->earned_at->format('M d, Y h:i A'),
                'status' => $credit->status,
                'source' => $this->getSourceName($credit),
            ];
        });

        return Inertia::render('cme-credits/history', [
            'credits' => $credits,
        ]);
    }

    /**
     * Get credit statistics (API endpoint).
     */
    public function statistics(Request $request): JsonResponse
    {
        // Check if CME/CPD tracking is enabled
        if (! config('cme_cpd.enabled', true)) {
            return response()->json(['error' => 'CME/CPD tracking is not enabled.'], 404);
        }

        $user = $request->user();
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $totalCredits = CmeCredit::getTotalCreditsForUser($user->id, $startDate, $endDate);

        $creditsBySource = CmeCredit::where('user_id', $user->id)
            ->where('status', 'approved')
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('earned_at', [$startDate, $endDate]);
            })
            ->selectRaw('source_type, SUM(credits) as total')
            ->groupBy('source_type')
            ->get()
            ->pluck('total', 'source_type')
            ->toArray();

        return response()->json([
            'total_credits' => $totalCredits,
            'credits_by_source' => $creditsBySource,
        ]);
    }

    /**
     * Get source name for display.
     */
    private function getSourceName(CmeCredit $credit): ?string
    {
        return match ($credit->source_type) {
            'event' => $credit->event?->title,
            'exam_institution' => $credit->attempt?->assessment?->title,
            'exam_national' => $credit->attempt?->assessment?->title,
            'assignment_submission' => $credit->assignment?->title,
            default => null,
        };
    }
}
