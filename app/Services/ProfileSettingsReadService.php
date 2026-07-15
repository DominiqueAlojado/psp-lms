<?php

namespace App\Services;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;

class ProfileSettingsReadService
{
    public function editPayload(Request $request): array
    {
        return [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ];
    }
}
