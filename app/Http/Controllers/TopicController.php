<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    /**
     * Get topics for the current organization (including global topics).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $organizationId = $user->current_organization_id;

        $topics = Topic::query()
            ->where(function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId)
                    ->orWhere('is_global', true);
            })
            ->orderBy('is_global', 'desc')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_global']);

        return response()->json($topics);
    }

    /**
     * Store a new topic.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $topic = Topic::create([
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
