<?php

namespace App\Services;

use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use Laravel\Fortify\Features;

class TwoFactorAuthenticationReadService
{
    public function showPayload(TwoFactorAuthenticationRequest $request): array
    {
        $request->ensureStateIsValid();

        return [
            'twoFactorEnabled' => $request->user()->hasEnabledTwoFactorAuthentication(),
            'requiresConfirmation' => Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
        ];
    }
}
