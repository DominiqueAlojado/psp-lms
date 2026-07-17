<?php

namespace App\Repositories\Eloquent;

use App\Models\Institution\InstitutionAttempt;
use App\Models\National\NationalAttempt;
use App\Models\Resident;
use App\Repositories\Contracts\GradebookRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GradebookRepository implements GradebookRepositoryInterface
{
    public function getCompletedInstitutionAttemptsForUser(int $userId, bool $withAssessment = false): Collection
    {
        $query = InstitutionAttempt::query()
            ->where('user_id', $userId)
            ->where('status', 'completed');

        if ($withAssessment) {
            $query->with(['assessment.questions.topic', 'answers']);
        }

        return $query->orderBy('submitted_at', 'desc')->get();
    }

    public function getCompletedNationalAttemptsForUser(int $userId, bool $withAssessment = false): Collection
    {
        $query = NationalAttempt::query()
            ->where('user_id', $userId)
            ->where('status', 'completed');

        if ($withAssessment) {
            $query->with(['assessment.questions.topicRecord', 'answers']);
        }

        return $query->orderBy('submitted_at', 'desc')->get();
    }

    public function getAllResidents(): Collection
    {
        return Resident::query()
            ->whereNotNull('user_id')
            ->with('user')
            ->orderBy('last_name')
            ->get();
    }

    public function getResidentsForOrganization(int $organizationId): Collection
    {
        return Resident::query()
            ->where('organization_id', $organizationId)
            ->with('user')
            ->orderBy('last_name')
            ->get();
    }

    public function getResidentsForOrganizations(array $organizationIds): Collection
    {
        return Resident::query()
            ->whereIn('organization_id', $organizationIds)
            ->with(['user', 'organization'])
            ->orderBy('last_name')
            ->get();
    }

    public function getInstitutionTopicPerformanceRows(int $userId): Collection
    {
        return DB::table('institution_answers as a')
            ->join('institution_attempts as at', 'a.attempt_id', '=', 'at.id')
            ->join('institution_questions as q', 'a.question_id', '=', 'q.id')
            ->leftJoin('topics as t', 'q.topic_id', '=', 't.id')
            ->where('at.user_id', $userId)
            ->where('at.status', 'completed')
            ->whereNull('a.deleted_at')
            ->whereNull('at.deleted_at')
            ->whereNull('q.deleted_at')
            ->select([
                't.name as topic_name',
                't.id as topic_id',
                DB::raw('COUNT(*) as total_questions'),
                DB::raw('SUM(CASE WHEN a.is_correct THEN 1 ELSE 0 END) as correct_answers'),
                DB::raw('SUM(a.points_earned) as total_points_earned'),
                DB::raw('SUM(q.points) as total_possible_points'),
            ])
            ->groupBy('t.id', 't.name')
            ->get();
    }

    public function getNationalTopicPerformanceRows(int $userId): Collection
    {
        return DB::table('national_answers as a')
            ->join('national_attempts as at', 'a.attempt_id', '=', 'at.id')
            ->join('national_questions as q', 'a.question_id', '=', 'q.id')
            ->leftJoin('topics as t', 'q.topic_id', '=', 't.id')
            ->where('at.user_id', $userId)
            ->where('at.status', 'completed')
            ->whereNull('a.deleted_at')
            ->whereNull('at.deleted_at')
            ->whereNull('q.deleted_at')
            ->select([
                't.name as topic_name',
                't.id as topic_id',
                DB::raw('COUNT(*) as total_questions'),
                DB::raw('SUM(CASE WHEN a.is_correct THEN 1 ELSE 0 END) as correct_answers'),
                DB::raw('SUM(a.points_earned) as total_points_earned'),
                DB::raw('SUM(q.points) as total_possible_points'),
            ])
            ->groupBy('t.id', 't.name')
            ->get();
    }
}
