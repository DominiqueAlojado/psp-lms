<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupportTicketMessageRequest;
use App\Http\Requests\StoreSupportTicketRequest;
use App\Http\Requests\UpdateSupportTicketRequest;
use App\Models\SupportTicket;
use App\Services\SupportManagementService;
use App\Services\SupportReadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportController extends Controller
{
    public function __construct(
        private readonly SupportReadService $supportReadService,
        private readonly SupportManagementService $supportManagementService,
    ) {}

    public function index(Request $request): Response
    {
        $payload = $this->supportReadService->indexPayload(
            $request->user(),
            $request->only(['status'])
        );

        return Inertia::render('support/index', [
            'tickets' => $payload['tickets'],
            'summary' => $payload['summary'],
            'categories' => $payload['categories'],
            'priorities' => $payload['priorities'],
            'statuses' => $payload['statuses'],
            'filters' => $request->only(['status']),
            'canManage' => $payload['canManage'],
            'isAllOrganizationsContext' => $payload['isAllOrganizationsContext'],
            'canCreateTicket' => $payload['canCreateTicket'],
            'showsManagedTickets' => $payload['showsManagedTickets'],
        ]);
    }

    public function store(StoreSupportTicketRequest $request): RedirectResponse
    {
        $this->supportManagementService->create($request->user(), $request->validated());

        return redirect('/support')->with('success', 'Support ticket submitted successfully.');
    }

    public function manage(Request $request): Response
    {
        $payload = $this->supportReadService->managePayload(
            $request->user(),
            $request->only(['search', 'status', 'priority', 'category'])
        );

        return Inertia::render('support/manage', [
            'tickets' => $payload['tickets'],
            'summary' => $payload['summary'],
            'categories' => $payload['categories'],
            'priorities' => $payload['priorities'],
            'statuses' => $payload['statuses'],
            'filters' => $request->only(['search', 'status', 'priority', 'category']),
            'isAllOrganizationsContext' => $payload['isAllOrganizationsContext'],
        ]);
    }

    public function show(Request $request, SupportTicket $ticket): Response
    {
        $payload = $this->supportReadService->showPayload($request->user(), $ticket);

        return Inertia::render('support/show', $payload);
    }

    public function update(UpdateSupportTicketRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->supportManagementService->update($request->user(), $ticket, $request->validated());

        return back()->with('success', 'Support ticket updated successfully.');
    }

    public function storeMessage(StoreSupportTicketMessageRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->supportManagementService->addReply(
            $request->user(),
            $ticket,
            $request->validated()['message']
        );

        return back()->with('success', 'Reply sent successfully.');
    }
}
