<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\TopicRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class TopicController extends Controller
{
    public function __construct(
        private readonly TopicRepositoryInterface $topicRepository,
    ) {}

    /**
     * Get topics for the current organization (including global topics).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $topics = $this->topicRepository->getForOrganizationWithGlobals($user->current_organization_id);

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

        $topic = $this->topicRepository->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'organization_id' => $request->user()->current_organization_id,
            'is_global' => false,
        ]);

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
