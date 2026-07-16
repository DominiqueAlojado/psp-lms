<?php

namespace App\Http\Controllers;

use App\Services\NotificationReadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationReadService $notificationReadService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('notifications/index', $this->notificationReadService->indexPayload($request->user()));
    }

    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        $this->notificationReadService->markAsRead($request->user(), $notification);

        $target = $request->input('redirect_to');

        if (is_string($target) && $target !== '') {
            return redirect($target);
        }

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $this->notificationReadService->markAllAsRead($request->user());

        return back()->with('success', 'All notifications marked as read.');
    }
}
