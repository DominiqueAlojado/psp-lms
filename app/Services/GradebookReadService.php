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

        return [
            'stats' => $this->calculateUserStats($user->id, $isNational),
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
            ],
            'stats' => $this->calculateUserStats($resident->user_id),
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
}
