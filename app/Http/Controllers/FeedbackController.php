<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeedbackRequest;
use App\Models\FeedbackEntry;
use App\Services\FeedbackManagementService;
use App\Services\FeedbackReadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeedbackController extends Controller
{
    public function __construct(
        private readonly FeedbackReadService $feedbackReadService,
        private readonly FeedbackManagementService $feedbackManagementService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('feedback/index', $this->feedbackReadService->indexPayload($request->user()));
    }

    public function manage(Request $request): Response
    {
        return Inertia::render('feedback/manage', $this->feedbackReadService->managePayload(
            $request->user(),
            $request->only(['search', 'module_name', 'would_recommend'])
        ));
    }

    public function store(StoreFeedbackRequest $request): RedirectResponse
    {
        $this->feedbackManagementService->create($request->user(), $request->validated());

        $query = $request->only(['org']);
        $target = '/feedback';

        if (! empty(array_filter($query, fn ($value) => $value !== null && $value !== ''))) {
            $target .= '?' . http_build_query($query);
        }

        return redirect($target)->with('success', 'Feedback submitted successfully.');
    }

    public function destroy(Request $request, FeedbackEntry $feedbackEntry): RedirectResponse
    {
        $this->feedbackManagementService->delete($request->user(), $feedbackEntry);

        return back()->with('success', 'Feedback entry deleted successfully.');
    }
}
