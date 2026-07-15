<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileSettingsManagementService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function updateProfile(User $user, array $validated): void
    {
        $this->userRepository->updateProfile($user, $validated);
    }

    public function deleteAccount(Request $request): void
    {
        $user = $request->user();
        $user->releaseActiveExamSessions($request->session()->getId());
        Auth::logout();
        $this->userRepository->delete($user);
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
