<?php

namespace App\Services;

use App\Models\Resident;
use App\Models\User;
use App\Repositories\Contracts\GradebookRepositoryInterface;

class GradebookReadService
{
    private const ALL_ORGANIZATIONS_SLUG = 'all-organizations';

    public function __construct(
        private readonly GradebookRepositoryInterface $gradebookRepository,
    ) {}

    public function myGradesPayload(User $user): array
    {
        $currentOrganization = $user->currentOrganization;
        $isNational = $currentOrganization?->type === 'national';
        $resident = $user->resident?->loadMissing('organization');
        $nationalAttempts = $isNational
            ? $this->gradebookRepository->getCompletedNationalAttemptsForUser($user->id, true)
            : collect();

        return [
            'stats' => $this->calculateUserStats($user->id, $isNational),
            'comparison' => $resident
                ? $this->buildResidentComparisonPayload($resident, $isNational)
                : null,
            'nationalStanding' => $this->buildResidentNationalStandingPayload($nationalAttempts),
            'categoryPerformance' => $isNational ? [] : $this->getCategoryPerformance($user->id),
            'topicPerformance' => $this->getTopicPerformance($user->id, $isNational),
            'recentExams' => $this->getRecentExams($user->id, 10, $isNational),
            'performanceTrend' => $this->getPerformanceTrend($user->id, $isNational),
        ];
    }

    public function indexPayload(User $user): array
    {
        if (data_get($user->currentOrganization, 'slug') === self::ALL_ORGANIZATIONS_SLUG) {
            $organizationIds = $user->organizations()
                ->wherePivot('organization_user.is_active', true)
                ->pluck('organizations.id')
                ->all();

            if ($organizationIds === []) {
                abort(403, 'No organization selected.');
            }

            $residents = $this->gradebookRepository
                ->getResidentsForOrganizations($organizationIds)
                ->map(fn ($resident) => [
                    'id' => $resident->id,
                    'name' => $resident->full_name,
                    'year_level' => $resident->year_level,
                    'status' => $resident->status,
                    'organization_name' => $resident->organization?->name,
                    'stats' => $this->calculateUserStats($resident->user_id),
                ]);

            return [
                'residents' => $residents,
            ];
        }

        $organizationId = $user->currentOrganization?->id;

        if (! $organizationId) {
            abort(403, 'No organization selected.');
        }

        $residents = $this->gradebookRepository
            ->getResidentsForOrganization($organizationId)
            ->map(fn ($resident) => [
                'id' => $resident->id,
                'name' => $resident->full_name,
                'year_level' => $resident->year_level,
                'status' => $resident->status,
                'stats' => $this->calculateUserStats($resident->user_id),
            ]);

        return [
            'residents' => $residents,
        ];
    }

    public function showPayload(User $user, Resident $resident): array
    {
        $organizationId = $user->currentOrganization?->id;
        $isAllOrganizationsContext = data_get($user->currentOrganization, 'slug') === self::ALL_ORGANIZATIONS_SLUG;
        $allowedOrganizationIds = $isAllOrganizationsContext
            ? $user->organizations()->wherePivot('organization_user.is_active', true)->pluck('organizations.id')->all()
            : [$organizationId];

        if (! in_array($resident->organization_id, array_filter($allowedOrganizationIds), true)) {
            abort(403, 'You do not have access to this resident.');
        }

        $institutionAttempts = $this->gradebookRepository
            ->getCompletedInstitutionAttemptsForUser($resident->user_id, true)
            ->map(fn ($attempt) => [
                'id' => $attempt->id,
                'type' => 'institution',
                'exam_title' => $attempt->assessment->title,
                'exam_category' => $attempt->assessment->exam_category,
                'score' => $attempt->score,
                'total_points' => $attempt->total_points,
                'percentage' => round($attempt->percentage, 2),
                'passing_score' => $attempt->assessment->passing_score,
                'passed' => $attempt->isPassed(),
                'submitted_at' => $attempt->submitted_at?->format('Y-m-d H:i:s'),
            ]);

        $nationalAttempts = $this->gradebookRepository
            ->getCompletedNationalAttemptsForUser($resident->user_id, true)
            ->map(fn ($attempt) => [
                'id' => $attempt->id,
                'type' => 'national',
                'exam_title' => $attempt->assessment->title,
                'exam_year' => $attempt->assessment->exam_year,
                'score' => $attempt->score,
                'total_points' => $attempt->total_points,
                'percentage' => round($attempt->percentage, 2),
                'passing_score' => $attempt->assessment->passing_score,
                'passed' => $attempt->isPassed(),
                'national_rank' => $attempt->national_rank,
                'institution_rank' => $attempt->institution_rank,
                'percentile' => $attempt->percentile,
                'submitted_at' => $attempt->submitted_at?->format('Y-m-d H:i:s'),
            ]);

        return [
            'resident' => [
                'id' => $resident->id,
                'name' => $resident->full_name,
                'year_level' => $resident->year_level,
                'status' => $resident->status,
                'course' => $resident->course,
                'organization_name' => $resident->organization?->name,
            ],
            'stats' => $this->calculateUserStats($resident->user_id),
            'comparison' => $this->buildComparisonPayload($resident),
            'categoryPerformance' => $this->getCategoryPerformance($resident->user_id),
            'topicPerformance' => $this->getTopicPerformance($resident->user_id),
            'recentExams' => $this->getRecentExams($resident->user_id, 20),
            'performanceTrend' => $this->getPerformanceTrend($resident->user_id),
            'institutionAttempts' => $institutionAttempts,
            'nationalAttempts' => $nationalAttempts,
        ];
    }

    public function calculateUserStats(int $userId, bool $isNational = false): array
    {
        $institutionAttempts = $isNational
            ? collect()
            : $this->gradebookRepository->getCompletedInstitutionAttemptsForUser($userId);
        $nationalAttempts = $isNational
            ? $this->gradebookRepository->getCompletedNationalAttemptsForUser($userId)
            : collect();

        $allAttempts = $institutionAttempts->merge($nationalAttempts);

        if ($allAttempts->isEmpty()) {
            return [
                'total_exams' => 0,
                'total_institution_exams' => 0,
                'total_national_exams' => 0,
                'average_score' => 0,
                'average_percentage' => 0,
                'total_passed' => 0,
                'total_failed' => 0,
                'pass_rate' => 0,
                'highest_score' => 0,
                'lowest_score' => 0,
            ];
        }

        $totalScore = $allAttempts->sum('score');
        $totalPoints = $allAttempts->sum('total_points');
        $totalPassed = $allAttempts->filter(fn ($attempt) => $attempt->isPassed())->count();

        return [
            'total_exams' => $allAttempts->count(),
            'total_institution_exams' => $institutionAttempts->count(),
            'total_national_exams' => $nationalAttempts->count(),
            'average_score' => $totalPoints > 0 ? round(($totalScore / $totalPoints) * 100, 2) : 0,
            'average_percentage' => round($allAttempts->avg('percentage'), 2),
            'total_passed' => $totalPassed,
            'total_failed' => $allAttempts->count() - $totalPassed,
            'pass_rate' => round(($totalPassed / $allAttempts->count()) * 100, 2),
            'highest_score' => round($allAttempts->max('percentage'), 2),
            'lowest_score' => round($allAttempts->min('percentage'), 2),
        ];
    }

    public function getCategoryPerformance(int $userId): array
    {
        return $this->gradebookRepository
            ->getCompletedInstitutionAttemptsForUser($userId, true)
            ->groupBy(fn ($attempt) => $attempt->assessment->exam_category ?? 'Uncategorized')
            ->map(fn ($attempts, $category) => [
                'category' => $category,
                'exam_count' => $attempts->count(),
                'average_percentage' => round($attempts->avg('percentage'), 2),
                'total_passed' => $attempts->filter(fn ($attempt) => $attempt->isPassed())->count(),
                'total_failed' => $attempts->count() - $attempts->filter(fn ($attempt) => $attempt->isPassed())->count(),
                'pass_rate' => round(($attempts->filter(fn ($attempt) => $attempt->isPassed())->count() / $attempts->count()) * 100, 2),
            ])
            ->values()
            ->sortByDesc('exam_count')
            ->toArray();
    }

    public function getTopicPerformance(int $userId, bool $isNational = false): array
    {
        $institutionTopics = $isNational ? collect() : $this->gradebookRepository->getInstitutionTopicPerformanceRows($userId);
        $nationalTopics = $isNational ? $this->gradebookRepository->getNationalTopicPerformanceRows($userId) : collect();

        $topicMap = [];

        foreach ($institutionTopics->merge($nationalTopics) as $topic) {
            $topicName = $topic->topic_name ?? 'Uncategorized';

            if (! isset($topicMap[$topicName])) {
                $topicMap[$topicName] = [
                    'topic' => $topicName,
                    'total_questions' => 0,
                    'correct_answers' => 0,
                    'total_points_earned' => 0,
                    'total_possible_points' => 0,
                ];
            }

            $topicMap[$topicName]['total_questions'] += $topic->total_questions;
            $topicMap[$topicName]['correct_answers'] += $topic->correct_answers;
            $topicMap[$topicName]['total_points_earned'] += $topic->total_points_earned;
            $topicMap[$topicName]['total_possible_points'] += $topic->total_possible_points;
        }

        return collect($topicMap)->map(function ($data) {
            $accuracy = $data['total_questions'] > 0
                ? round(($data['correct_answers'] / $data['total_questions']) * 100, 2)
                : 0;

            $scorePercentage = $data['total_possible_points'] > 0
                ? round(($data['total_points_earned'] / $data['total_possible_points']) * 100, 2)
                : 0;

            return [
                'topic' => $data['topic'],
                'total_questions' => $data['total_questions'],
                'correct_answers' => $data['correct_answers'],
                'accuracy' => $accuracy,
                'score_percentage' => $scorePercentage,
            ];
        })
            ->sortByDesc('total_questions')
            ->values()
            ->toArray();
    }

    public function getRecentExams(int $userId, int $limit = 10, bool $isNational = false): array
    {
        $institutionAttempts = $isNational
            ? collect()
            : $this->gradebookRepository
                ->getCompletedInstitutionAttemptsForUser($userId, true)
                ->filter(fn ($attempt) => $attempt->assessment !== null)
                ->map(fn ($attempt) => [
                    'type' => 'Institution',
                    'title' => $attempt->assessment->title,
                    'category' => $attempt->assessment->exam_category,
                    'score' => $attempt->score,
                    'total_points' => $attempt->total_points,
                    'percentage' => round($attempt->percentage, 2),
                    'passed' => $attempt->isPassed(),
                    'submitted_at' => $attempt->submitted_at,
                ]);

        $nationalAttempts = $isNational
            ? $this->gradebookRepository
                ->getCompletedNationalAttemptsForUser($userId, true)
                ->filter(fn ($attempt) => $attempt->assessment !== null)
                ->map(fn ($attempt) => [
                    'type' => 'National',
                    'title' => $attempt->assessment->title,
                    'category' => 'In-Service Exam',
                    'score' => $attempt->score,
                    'total_points' => $attempt->total_points,
                    'percentage' => round($attempt->percentage, 2),
                    'passed' => $attempt->isPassed(),
                    'submitted_at' => $attempt->submitted_at,
                ])
            : collect();

        return $institutionAttempts->concat($nationalAttempts)
            ->sortByDesc(fn ($attempt) => $attempt['submitted_at'] ?? '1970-01-01 00:00:00')
            ->take($limit)
            ->values()
            ->toArray();
    }

    public function getPerformanceTrend(int $userId, bool $isNational = false): array
    {
        $institutionAttempts = $isNational
            ? collect()
            : $this->gradebookRepository
                ->getCompletedInstitutionAttemptsForUser($userId)
                ->map(fn ($attempt) => [
                    'date' => $attempt->submitted_at?->format('Y-m-d'),
                    'percentage' => round($attempt->percentage, 2),
                ]);

        $nationalAttempts = $isNational
            ? $this->gradebookRepository
                ->getCompletedNationalAttemptsForUser($userId)
                ->map(fn ($attempt) => [
                    'date' => $attempt->submitted_at?->format('Y-m-d'),
                    'percentage' => round($attempt->percentage, 2),
                ])
            : collect();

        return $institutionAttempts->merge($nationalAttempts)
            ->sortBy('date')
            ->values()
            ->toArray();
    }

    private function buildComparisonPayload(Resident $resident): array
    {
        $cohort = $this->gradebookRepository
            ->getResidentsForOrganization($resident->organization_id)
            ->filter(fn ($peer) => $peer->user_id !== null)
            ->map(function ($peer) {
                return [
                    'resident_id' => $peer->id,
                    'name' => $peer->full_name,
                    'year_level' => $peer->year_level,
                    'stats' => $this->calculateUserStats($peer->user_id),
                ];
            })
            ->filter(fn (array $peer) => $peer['stats']['total_exams'] > 0)
            ->values();

        $residentStats = $cohort->firstWhere('resident_id', $resident->id)['stats'] ?? $this->calculateUserStats($resident->user_id);
        $sameLevel = $cohort->where('year_level', $resident->year_level)->values();

        $organizationLeaderboard = $cohort
            ->sortByDesc(fn (array $peer) => $peer['stats']['average_percentage'])
            ->values();

        $sameLevelLeaderboard = $sameLevel
            ->sortByDesc(fn (array $peer) => $peer['stats']['average_percentage'])
            ->values();

        $organizationRank = $organizationLeaderboard->search(fn (array $peer) => $peer['resident_id'] === $resident->id);
        $sameLevelRank = $sameLevelLeaderboard->search(fn (array $peer) => $peer['resident_id'] === $resident->id);

        $organizationAverage = $cohort->isNotEmpty()
            ? round($cohort->avg(fn (array $peer) => $peer['stats']['average_percentage']), 2)
            : 0;

        $sameLevelAverage = $sameLevel->isNotEmpty()
            ? round($sameLevel->avg(fn (array $peer) => $peer['stats']['average_percentage']), 2)
            : 0;

        return [
            'organization_name' => $resident->organization?->name,
            'resident_average_percentage' => round($residentStats['average_percentage'], 2),
            'same_year_level_average_percentage' => $sameLevelAverage,
            'organization_average_percentage' => $organizationAverage,
            'same_year_level_gap' => round($residentStats['average_percentage'] - $sameLevelAverage, 2),
            'organization_gap' => round($residentStats['average_percentage'] - $organizationAverage, 2),
            'same_year_level_rank' => $sameLevelRank === false ? null : $sameLevelRank + 1,
            'same_year_level_total' => $sameLevel->count(),
            'organization_rank' => $organizationRank === false ? null : $organizationRank + 1,
            'organization_total' => $cohort->count(),
            'top_same_year_level_residents' => $sameLevelLeaderboard
                ->take(3)
                ->map(fn (array $peer) => [
                    'resident_id' => $peer['resident_id'],
                    'name' => $peer['name'],
                    'average_percentage' => round($peer['stats']['average_percentage'], 2),
                ])
                ->values()
                ->toArray(),
            'top_organization_residents' => $organizationLeaderboard
                ->take(5)
                ->map(fn (array $peer) => [
                    'resident_id' => $peer['resident_id'],
                    'name' => $peer['name'],
                    'year_level' => $peer['year_level'],
                    'average_percentage' => round($peer['stats']['average_percentage'], 2),
                ])
                ->values()
                ->toArray(),
        ];
    }

    private function buildResidentComparisonPayload(Resident $resident, bool $isNational = false): array
    {
        $organizationCohort = $this->gradebookRepository
            ->getResidentsForOrganization($resident->organization_id)
            ->filter(fn ($peer) => $peer->user_id !== null)
            ->map(function ($peer) use ($isNational) {
                return [
                    'resident_id' => $peer->id,
                    'year_level' => $peer->year_level,
                    'stats' => $this->calculateUserStats($peer->user_id, $isNational),
                ];
            })
            ->filter(fn (array $peer) => $peer['stats']['total_exams'] > 0)
            ->values();

        $comparisonCohort = $isNational
            ? $this->gradebookRepository
                ->getAllResidents()
                ->map(function ($peer) {
                    return [
                        'resident_id' => $peer->id,
                        'year_level' => $peer->year_level,
                        'stats' => $this->calculateUserStats($peer->user_id, true),
                    ];
                })
                ->filter(fn (array $peer) => $peer['stats']['total_exams'] > 0)
                ->values()
            : $organizationCohort;

        $residentStats = $comparisonCohort->firstWhere('resident_id', $resident->id)['stats']
            ?? $this->calculateUserStats($resident->user_id, $isNational);
        $comparisonGroup = $isNational
            ? $comparisonCohort
            : $comparisonCohort->where('year_level', $resident->year_level)->values();

        $organizationLeaderboard = $organizationCohort
            ->sortByDesc(fn (array $peer) => $peer['stats']['average_percentage'])
            ->values();

        $comparisonLeaderboard = $comparisonGroup
            ->sortByDesc(fn (array $peer) => $peer['stats']['average_percentage'])
            ->values();

        $organizationRank = $organizationLeaderboard->search(fn (array $peer) => $peer['resident_id'] === $resident->id);
        $comparisonRank = $comparisonLeaderboard->search(fn (array $peer) => $peer['resident_id'] === $resident->id);

        $organizationAverage = $organizationCohort->isNotEmpty()
            ? round($organizationCohort->avg(fn (array $peer) => $peer['stats']['average_percentage']), 2)
            : 0;

        $comparisonAverage = $comparisonGroup->isNotEmpty()
            ? round($comparisonGroup->avg(fn (array $peer) => $peer['stats']['average_percentage']), 2)
            : 0;

        $yearLevelBreakdown = $isNational
            ? $this->buildNationalYearLevelBreakdown($comparisonCohort, $residentStats['average_percentage'])
            : [];

        return [
            'year_level' => $resident->year_level,
            'organization_name' => $resident->organization?->name,
            'comparison_group_label' => $isNational ? 'All Year Levels' : 'Same Year Level',
            'resident_average_percentage' => round($residentStats['average_percentage'], 2),
            'same_year_level_average_percentage' => $comparisonAverage,
            'organization_average_percentage' => $organizationAverage,
            'same_year_level_gap' => round($residentStats['average_percentage'] - $comparisonAverage, 2),
            'organization_gap' => round($residentStats['average_percentage'] - $organizationAverage, 2),
            'same_year_level_rank' => $comparisonRank === false ? null : $comparisonRank + 1,
            'same_year_level_total' => $comparisonGroup->count(),
            'organization_rank' => $organizationRank === false ? null : $organizationRank + 1,
            'organization_total' => $organizationCohort->count(),
            'peer_names_visible' => false,
            'is_national_context' => $isNational,
            'year_level_breakdown' => $yearLevelBreakdown,
        ];
    }

    private function buildNationalYearLevelBreakdown(\Illuminate\Support\Collection $comparisonCohort, float $residentAverage): array
    {
        $orderedLevels = [
            'Pre-Resident',
            'First Year',
            'Second Year',
            'Third Year',
            'Fourth Year',
            'Graduate',
        ];

        return collect($orderedLevels)
            ->map(function (string $yearLevel) use ($comparisonCohort, $residentAverage) {
                $levelPeers = $comparisonCohort
                    ->where('year_level', $yearLevel)
                    ->sortByDesc(fn (array $peer) => $peer['stats']['average_percentage'])
                    ->values();

                if ($levelPeers->isEmpty()) {
                    return null;
                }

                $average = round($levelPeers->avg(fn (array $peer) => $peer['stats']['average_percentage']), 2);

                return [
                    'year_level' => $yearLevel,
                    'average_percentage' => $average,
                    'total_residents' => $levelPeers->count(),
                    'resident_gap' => round($residentAverage - $average, 2),
                    'top_average_percentage' => round(
                        (float) ($levelPeers->first()['stats']['average_percentage'] ?? 0),
                        2,
                    ),
                ];
            })
            ->filter()
            ->values()
            ->toArray();
    }

    private function buildResidentNationalStandingPayload(\Illuminate\Support\Collection $nationalAttempts): ?array
    {
        $latestComparableAttempt = $nationalAttempts
            ->filter(fn ($attempt) => $attempt->assessment !== null)
            ->first(function ($attempt) {
                return $attempt->assessment->national_ranking_enabled
                    || $attempt->assessment->institution_comparison_enabled;
            });

        if (! $latestComparableAttempt) {
            return null;
        }

        $assessment = $latestComparableAttempt->assessment;

        return [
            'exam_title' => $assessment->title,
            'exam_year' => $assessment->exam_year,
            'national_ranking_enabled' => (bool) $assessment->national_ranking_enabled,
            'institution_comparison_enabled' => (bool) $assessment->institution_comparison_enabled,
            'national_rank' => $assessment->national_ranking_enabled
                ? $latestComparableAttempt->national_rank
                : null,
            'institution_rank' => $assessment->institution_comparison_enabled
                ? $latestComparableAttempt->institution_rank
                : null,
            'percentile' => $assessment->national_ranking_enabled
                ? $latestComparableAttempt->percentile
                : null,
        ];
    }
}
