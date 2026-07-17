<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSystemConfigRequest;
use App\Services\SystemConfigManagementService;
use App\Services\SystemConfigReadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SystemConfigController extends Controller
{
    public function __construct(
        private readonly SystemConfigReadService $readService,
        private readonly SystemConfigManagementService $managementService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render(
            'settings/configurations',
            $this->readService->indexPayload($request->user()),
        );
    }

    public function update(UpdateSystemConfigRequest $request): RedirectResponse
    {
        $request->validated();

        $this->managementService->updateConfigs(
            $request->user(),
            (array) $request->input('configs', []),
        );

        return back()->with('success', 'System configuration updated successfully.');
    }
}
