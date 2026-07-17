<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse($request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        if ($user?->hasRole('Resident')) {
            $request->session()->flash(
                'info',
                'Demo notice: the data shown in this resident portal is for demonstration purposes only and may include dummy sample content.',
            );
        }

        return redirect()->intended(config('fortify.home'));
    }
}
