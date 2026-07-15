<?php

namespace App\Http\Controllers;

use App\Services\ActivityReadService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    public function __construct(
        private readonly ActivityReadService $activityReadService,
    ) {}

    public function index(Request $request): Response
    {
        $payload = $this->activityReadService->indexPayload(
            $request->user(),
            $request->only(['search', 'module', 'date_from', 'date_to'])
        );

        return Inertia::render('activities/index', $payload);
    }
}
