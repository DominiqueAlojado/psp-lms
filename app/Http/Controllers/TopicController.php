<?php

namespace App\Http\Controllers;

use App\Services\TopicManagementService;
use App\Services\TopicReadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class TopicController extends Controller
{
    public function __construct(
        private readonly TopicReadService $topicReadService,
        private readonly TopicManagementService $topicManagementService,
    ) {}

    /**
     * Get topics for the current organization (including global topics).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $topics = $this->topicReadService->listForOrganization($user->current_organization_id);

        return response()->json($topics);
    }

    /**
     * Store a new topic.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $topic = $this->topicManagementService->createForOrganization(
            $request->user()->current_organization_id,
            $validated
        );

        return back()->with([
            'success' => 'Topic created successfully',
            'topic' => [
                'id' => $topic->id,
                'name' => $topic->name,
                'slug' => $topic->slug,
                'is_global' => $topic->is_global,
            ],
        ]);
    }
}
