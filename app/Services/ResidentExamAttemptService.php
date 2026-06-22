<?php

namespace App\Services;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionAttempt;
use App\Models\National\NationalAssessment;
use App\Models\National\NationalAttempt;
use App\Models\User;
use App\Repositories\Contracts\ResidentExamRepositoryInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ResidentExamAttemptService
{
    public const EXAM_SESSION_CONFLICT_MESSAGE = 'This exam is already active in another browser or device.';

    public function __construct(
        private readonly ResidentExamRepositoryInterface $residentExamRepository,
    ) {}

    public function startOrReuseInstitutionAttempt(Request $request, InstitutionAssessment $assessment, User $user): InstitutionAttempt
    {
        $attempt = $this->residentExamRepository->findInstitutionStartedAttempt($assessment, $user->id);

        if (! $attempt) {
            $unstartedAttempt = $this->residentExamRepository->findInstitutionUnstartedAttempt($assessment, $user->id);

            if ($unstartedAttempt) {
                $this->residentExamRepository->updateAttempt($unstartedAttempt, [
                    'started_at' => now(),
                    'active_session_id' => $request->session()->getId(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'last_activity_at' => now(),
                ]);
                $attempt = $unstartedAttempt;
            } else {
                $attempt = $this->residentExamRepository->createInstitutionAttempt($assessment, [
                    'user_id' => $user->id,
                    'year_level' => $user->resident?->year_level,
                    'organization_id' => $user->current_organization_id,
                    'started_at' => now(),
                    'total_points' => $assessment->total_points,
                    'status' => 'in_progress',
                    'active_session_id' => $request->session()->getId(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'last_activity_at' => now(),
                ]);
            }
        }

        $this->claimOrAbortAttemptSession($request, $attempt);

        return $attempt;
    }

    public function startOrReuseNationalAttempt(Request $request, NationalAssessment $assessment, User $user): NationalAttempt
    {
        $attempt = $this->residentExamRepository->findNationalStartedAttempt($assessment, $user->id);

        if (! $attempt) {
            $unstartedAttempt = $this->residentExamRepository->findNationalUnstartedAttempt($assessment, $user->id);

            if ($unstartedAttempt) {
                $this->residentExamRepository->updateAttempt($unstartedAttempt, [
                    'started_at' => now(),
                    'active_session_id' => $request->session()->getId(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'browser_metadata' => $request->input('browser_metadata'),
                    'connection_type' => $request->input('connection_type'),
                    'connection_speed' => $request->input('connection_speed'),
                    'last_activity_at' => now(),
                ]);
                $attempt = $unstartedAttempt;
            } else {
                $attempt = $this->residentExamRepository->createNationalAttempt($assessment, [
                    'user_id' => $user->id,
                    'year_level' => $user->resident?->year_level,
                    'organization_id' => $user->current_organization_id,
                    'started_at' => now(),
                    'total_points' => $assessment->total_points,
                    'status' => 'in_progress',
                    'active_session_id' => $request->session()->getId(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'browser_metadata' => $request->input('browser_metadata'),
                    'connection_type' => $request->input('connection_type'),
                    'connection_speed' => $request->input('connection_speed'),
                    'last_activity_at' => now(),
                ]);
            }
        }

        $this->claimOrAbortAttemptSession($request, $attempt);

        return $attempt;
    }

    public function findOwnedAttemptOrFail(string $type, int $attemptId, User $user): InstitutionAttempt|NationalAttempt
    {
        $attempt = $type === 'institution'
            ? $this->residentExamRepository->findInstitutionAttemptOrFail($attemptId)
            : $this->residentExamRepository->findNationalAttemptOrFail($attemptId);

        if ($attempt->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        return $attempt;
    }

    public function claimOrAbortAttemptSession(Request $request, InstitutionAttempt|NationalAttempt $attempt): void
    {
        $currentSessionId = $request->session()->getId();

        if ($this->claimAttemptSessionIfAvailable($attempt, $currentSessionId)) {
            return;
        }

        if ($attempt->active_session_id !== $currentSessionId) {
            abort(409, self::EXAM_SESSION_CONFLICT_MESSAGE);
        }
    }

    public function ensureAttemptSessionAccess(Request $request, InstitutionAttempt|NationalAttempt $attempt): void
    {
        $currentSessionId = $request->session()->getId();

        if ($this->claimAttemptSessionIfAvailable($attempt, $currentSessionId)) {
            return;
        }

        if ($attempt->active_session_id !== $currentSessionId) {
            if ($request->expectsJson() || $request->ajax()) {
                response()->json([
                    'error' => self::EXAM_SESSION_CONFLICT_MESSAGE,
                ], 409)->throwResponse();
            }

            abort(409, self::EXAM_SESSION_CONFLICT_MESSAGE);
        }
    }

    private function claimAttemptSessionIfAvailable(InstitutionAttempt|NationalAttempt $attempt, string $currentSessionId): bool
    {
        if (! $attempt->active_session_id || $attempt->active_session_id === $currentSessionId) {
            if ($attempt->active_session_id !== $currentSessionId) {
                $this->residentExamRepository->updateAttempt($attempt, [
                    'active_session_id' => $currentSessionId,
                ]);
                $attempt->active_session_id = $currentSessionId;
            }

            return true;
        }

        if (! $this->residentExamRepository->examSessionExists($attempt->active_session_id)) {
            $this->residentExamRepository->updateAttempt($attempt, [
                'active_session_id' => $currentSessionId,
            ]);
            $attempt->active_session_id = $currentSessionId;

            return true;
        }

        return false;
    }
}
