<?php

namespace App\Http\Controllers;

use App\Models\Institution\InstitutionAttempt;
use App\Models\National\NationalAttempt;
use App\Models\Organization;
use App\Models\Resident;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class GradebookController extends Controller
{
    /**
     * Display the resident's personal performance dashboard.
     */
    public function myGrades(Request $request): Response
    {
        $user = $request->user();

        // Get all completed attempts for this user
        $institutionAttempts = InstitutionAttempt::with(['assessment'])
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->orderBy('submitted_at', 'desc')
            ->get();

        $nationalAttempts = NationalAttempt::with(['assessment'])
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->orderBy('submitted_at', 'desc')
            ->get();

        // Calculate overall statistics
        $stats = $this->calculateUserStats($user->id);

        // Performance by category (Institution exams only)
        $categoryPerformance = $this->getCategoryPerformance($user->id);

        // Performance by topic
        $topicPerformance = $this->getTopicPerformance($user->id);

        // Recent exam history
        $recentExams = $this->getRecentExams($user->id);

        // Performance trends (last 10 exams)
        $performanceTrend = $this->getPerformanceTrend($user->id);

        return Inertia::render('gradebook/my-grades', [
            'stats' => $stats,
            'categoryPerformance' => $categoryPerformance,
            'topicPerformance' => $topicPerformance,
            'recentExams' => $recentExams,
            'performanceTrend' => $performanceTrend,
        ]);
    }

    /**
     * Display gradebook for faculty/training officers (all residents).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $organizationId = $user->currentOrganization?->id;

        if (! $organizationId) {
            abort(403, 'No organization selected.');
        }

        // Get all residents in the organization
        $residents = Resident::where('organization_id', $organizationId)
            ->with('user')
            ->orderBy('last_name')
            ->get()
            ->map(function ($resident) {
                return [
                    'id' => $resident->id,
                    'name' => $resident->full_name,
                    'year_level' => $resident->year_level,
                    'status' => $resident->status,
                    'stats' => $this->calculateUserStats($resident->user_id),
                ];
            });

        return Inertia::render('assessment-reports/by-performance', [
            'residents' => $residents,
        ]);
    }

    /**
     * Display detailed performance report for a specific resident.
     */
    public function show(Request $request, Resident $resident): Response
    {
        $user = $request->user();
        $organizationId = $user->currentOrganization?->id;

        // Verify resident belongs to user's organization
        if ($resident->organization_id !== $organizationId) {
            abort(403, 'You do not have access to this resident.');
        }

        // Get detailed statistics
        $stats = $this->calculateUserStats($resident->user_id);
        $categoryPerformance = $this->getCategoryPerformance($resident->user_id);
        $topicPerformance = $this->getTopicPerformance($resident->user_id);
        $recentExams = $this->getRecentExams($resident->user_id, 20);
        $performanceTrend = $this->getPerformanceTrend($resident->user_id);

        // Get all attempts
        $institutionAttempts = InstitutionAttempt::with(['assessment'])
            ->where('user_id', $resident->user_id)
            ->where('status', 'completed')
            ->orderBy('submitted_at', 'desc')
            ->get()
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

        $nationalAttempts = NationalAttempt::with(['assessment'])
            ->where('user_id', $resident->user_id)
            ->where('status', 'completed')
            ->orderBy('submitted_at', 'desc')
            ->get()
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

        return Inertia::render('assessment-reports/resident-detail', [
            'resident' => [
                'id' => $resident->id,
                'name' => $resident->full_name,
                'year_level' => $resident->year_level,
                'status' => $resident->status,
                'course' => $resident->course,
            ],
            'stats' => $stats,
            'categoryPerformance' => $categoryPerformance,
            'topicPerformance' => $topicPerformance,
            'recentExams' => $recentExams,
            'performanceTrend' => $performanceTrend,
            'institutionAttempts' => $institutionAttempts,
            'nationalAttempts' => $nationalAttempts,
        ]);
    }

    /**
     * Calculate overall statistics for a user.
     */
    private function calculateUserStats(int $userId): array
    {
        $institutionAttempts = InstitutionAttempt::where('user_id', $userId)
            ->where('status', 'completed')
            ->get();

        $nationalAttempts = NationalAttempt::where('user_id', $userId)
            ->where('status', 'completed')
            ->get();

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

    /**
     * Get performance breakdown by exam category.
     */
    private function getCategoryPerformance(int $userId): array
    {
        $categoryStats = InstitutionAttempt::with('assessment')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->get()
            ->groupBy(fn ($attempt) => $attempt->assessment->exam_category ?? 'Uncategorized')
            ->map(function ($attempts, $category) {
                $totalScore = $attempts->sum('score');
                $totalPoints = $attempts->sum('total_points');
                $passed = $attempts->filter(fn ($attempt) => $attempt->isPassed())->count();

                return [
                    'category' => $category,
                    'exam_count' => $attempts->count(),
                    'average_percentage' => round($attempts->avg('percentage'), 2),
                    'total_passed' => $passed,
                    'total_failed' => $attempts->count() - $passed,
                    'pass_rate' => round(($passed / $attempts->count()) * 100, 2),
                ];
            })
            ->values()
            ->sortByDesc('exam_count')
            ->toArray();

        return $categoryStats;
    }

    /**
     * Get performance breakdown by topic.
     */
    private function getTopicPerformance(int $userId): array
    {
        // Get all answers with their questions and topics for this user
        $institutionTopics = DB::table('institution_answers as a')
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

        $nationalTopics = DB::table('national_answers as a')
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

        // Merge and aggregate both
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

        // Calculate percentages and sort
        $topicPerformance = collect($topicMap)->map(function ($data) {
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

        return $topicPerformance;
    }

    /**
     * Get recent exam attempts.
     */
    private function getRecentExams(int $userId, int $limit = 10): array
    {
        $institutionAttempts = InstitutionAttempt::with('assessment')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->get()
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

        $nationalAttempts = NationalAttempt::with('assessment')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->get()
            ->map(fn ($attempt) => [
                'type' => 'National',
                'title' => $attempt->assessment->title,
                'category' => 'In-Service Exam',
                'score' => $attempt->score,
                'total_points' => $attempt->total_points,
                'percentage' => round($attempt->percentage, 2),
                'passed' => $attempt->isPassed(),
                'submitted_at' => $attempt->submitted_at,
            ]);

        return $institutionAttempts
            ->merge($nationalAttempts)
            ->sortByDesc('submitted_at')
            ->take($limit)
            ->values()
            ->toArray();
    }

    /**
     * Get performance trend over time.
     */
    private function getPerformanceTrend(int $userId): array
    {
        $institutionAttempts = InstitutionAttempt::where('user_id', $userId)
            ->where('status', 'completed')
            ->select(['id', 'score', 'total_points', 'submitted_at'])
            ->get()
            ->map(fn ($attempt) => [
                'date' => $attempt->submitted_at?->format('Y-m-d'),
                'percentage' => round($attempt->percentage, 2),
            ]);

        $nationalAttempts = NationalAttempt::where('user_id', $userId)
            ->where('status', 'completed')
            ->select(['id', 'score', 'total_points', 'submitted_at'])
            ->get()
            ->map(fn ($attempt) => [
                'date' => $attempt->submitted_at?->format('Y-m-d'),
                'percentage' => round($attempt->percentage, 2),
            ]);

        return $institutionAttempts
            ->merge($nationalAttempts)
            ->sortBy('date')
            ->values()
            ->toArray();
    }
}
