<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsReadService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsReadService $analyticsReadService,
    ) {}

    public function examAnalytics(Request $request): Response
    {
        return Inertia::render('analytics/exam-analytics', $this->analyticsReadService->examAnalyticsPayload($request));
    }

    public function itemAnalysis(Request $request): Response
    {
        return Inertia::render('analytics/item-analysis', $this->analyticsReadService->itemAnalysisPayload($request));
    }

    public function topicPerformance(Request $request): Response
    {
        return Inertia::render('analytics/topic-performance', []);
    }

    public function questionBank(Request $request): Response
    {
        return Inertia::render('analytics/question-bank', $this->analyticsReadService->questionBankPayload($request));
    }

    public function categoryPerformance(Request $request): Response
    {
        return Inertia::render('analytics/category-performance', []);
    }

    public function trends(Request $request): Response
    {
        return Inertia::render('analytics/trends', []);
    }
}
