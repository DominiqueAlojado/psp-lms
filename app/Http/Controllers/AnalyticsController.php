<?php

namespace App\Http\Controllers;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionAttempt;
use App\Models\National\NationalAssessment;
use App\Models\National\NationalAttempt;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    /**
     * Display exam analytics for a specific exam.
     */
    public function examAnalytics(Request $request): Response
    {
        $user = $request->user();
        $organizationId = $user->current_organization_id;
        $canViewAllOrganizations = $user->hasPermissionTo('view-all-assessment-reports');

        // Get all exams for the dropdown
        $institutionExams = InstitutionAssessment::query()
            ->select('id', 'title', 'exam_category', 'total_points', 'passing_score')
            ->when(! $canViewAllOrganizations, function ($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })
            ->where('is_published', true)
            ->orderBy('title')
            ->get();

        $nationalExams = NationalAssessment::query()
            ->select('id', 'title', 'category', 'total_points', 'passing_score')
            ->where('is_published', true)
            ->orderBy('title')
            ->get();

        // Combine exams with prefixes
        $exams = collect();
        foreach ($institutionExams as $exam) {
            $exams->push([
                'id' => 'institution_' . $exam->id,
                'title' => $exam->title,
                'category' => $exam->exam_category,
                'type' => 'institution',
            ]);
        }
        foreach ($nationalExams as $exam) {
            $exams->push([
                'id' => 'national_' . $exam->id,
                'title' => $exam->title,
                'category' => $exam->category,
                'type' => 'national',
            ]);
        }

        // Get organizations for filter (if user has permission)
        $organizations = collect();
        if ($canViewAllOrganizations) {
            $organizations = Organization::where('type', 'institution')
                ->where('is_active', true)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        }

        // If exam is selected, calculate analytics
        $analytics = null;
        if ($request->filled('exam')) {
            $examFilter = $request->input('exam');
            $analytics = $this->calculateExamAnalytics($examFilter, $organizationId, $canViewAllOrganizations, $request);
        }

        return Inertia::render('analytics/exam-analytics', [
            'exams' => $exams,
            'organizations' => $organizations,
            'analytics' => $analytics,
            'filters' => [
                'exam' => $request->input('exam'),
                'organization' => $request->input('organization'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
            ],
        ]);
    }

    /**
     * Calculate analytics for a specific exam.
     */
    private function calculateExamAnalytics(
        string $examFilter,
        ?int $organizationId,
        bool $canViewAllOrganizations,
        Request $request
    ): array {
        $isInstitution = str_starts_with($examFilter, 'institution_');
        $isNational = str_starts_with($examFilter, 'national_');

        if ($isInstitution) {
            $examId = (int) str_replace('institution_', '', $examFilter);
            return $this->calculateInstitutionExamAnalytics($examId, $organizationId, $canViewAllOrganizations, $request);
        } elseif ($isNational) {
            $examId = (int) str_replace('national_', '', $examFilter);
            return $this->calculateNationalExamAnalytics($examId, $canViewAllOrganizations, $request);
        }

        return [];
    }

    /**
     * Calculate analytics for institution exam.
     */
    private function calculateInstitutionExamAnalytics(
        int $examId,
        ?int $organizationId,
        bool $canViewAllOrganizations,
        Request $request
    ): array {
        $exam = InstitutionAssessment::with(['questions.topic'])
            ->findOrFail($examId);

        $query = InstitutionAttempt::query()
            ->where('assessment_id', $examId)
            ->where('status', 'completed');

        if (! $canViewAllOrganizations) {
            $query->where('organization_id', $organizationId);
        }

        if ($request->filled('organization')) {
            $query->where('organization_id', $request->input('organization'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('submitted_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('submitted_at', '<=', $request->input('date_to'));
        }

        $attempts = $query->get();

        if ($attempts->isEmpty()) {
            return [
                'exam' => [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'category' => $exam->exam_category,
                    'total_points' => $exam->total_points,
                    'passing_score' => $exam->passing_score,
                ],
                'total_attempts' => 0,
                'pass_rate' => 0,
                'average_score' => 0,
                'average_percentage' => 0,
                'completion_rate' => 0,
                'question_stats' => [],
                'topic_breakdown' => [],
                'year_level_stats' => [],
            ];
        }

        $totalAttempts = $attempts->count();
        $passedAttempts = $attempts->filter(function ($attempt) use ($exam) {
            return $attempt->score >= $exam->passing_score;
        })->count();

        $averageScore = $attempts->avg('score');
        $averagePercentage = $attempts->avg(function ($attempt) use ($exam) {
            return ($attempt->score / $exam->total_points) * 100;
        });

        // Calculate question statistics
        $questionStats = $this->calculateQuestionStats($attempts, $exam);

        // Calculate topic breakdown
        $topicBreakdown = $this->calculateTopicBreakdown($attempts, $exam);

        // Calculate year level statistics
        $yearLevelStats = $this->calculateYearLevelStats($attempts);

        return [
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'category' => $exam->exam_category,
                'total_points' => $exam->total_points,
                'passing_score' => $exam->passing_score,
            ],
            'total_attempts' => $totalAttempts,
            'pass_rate' => round(($passedAttempts / $totalAttempts) * 100, 2),
            'average_score' => round($averageScore, 2),
            'average_percentage' => round($averagePercentage, 2),
            'completion_rate' => 100, // All attempts are completed
            'question_stats' => $questionStats,
            'topic_breakdown' => $topicBreakdown,
            'year_level_stats' => $yearLevelStats,
        ];
    }

    /**
     * Calculate analytics for national exam.
     */
    private function calculateNationalExamAnalytics(
        int $examId,
        bool $canViewAllOrganizations,
        Request $request
    ): array {
        $exam = NationalAssessment::with(['questions'])
            ->findOrFail($examId);

        $query = NationalAttempt::query()
            ->where('assessment_id', $examId)
            ->where('status', 'completed');

        if ($request->filled('date_from')) {
            $query->whereDate('submitted_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('submitted_at', '<=', $request->input('date_to'));
        }

        $attempts = $query->get();

        if ($attempts->isEmpty()) {
            return [
                'exam' => [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'category' => $exam->category,
                    'total_points' => $exam->total_points,
                    'passing_score' => $exam->passing_score,
                ],
                'total_attempts' => 0,
                'pass_rate' => 0,
                'average_score' => 0,
                'average_percentage' => 0,
                'completion_rate' => 0,
                'question_stats' => [],
                'topic_breakdown' => [],
                'year_level_stats' => [],
            ];
        }

        $totalAttempts = $attempts->count();
        $passedAttempts = $attempts->filter(function ($attempt) use ($exam) {
            return $attempt->score >= $exam->passing_score;
        })->count();

        $averageScore = $attempts->avg('score');
        $averagePercentage = $attempts->avg(function ($attempt) use ($exam) {
            return ($attempt->score / $exam->total_points) * 100;
        });

        // Calculate question statistics
        $questionStats = $this->calculateQuestionStats($attempts, $exam);

        // Calculate topic breakdown
        $topicBreakdown = $this->calculateTopicBreakdown($attempts, $exam);

        // Calculate year level statistics
        $yearLevelStats = $this->calculateYearLevelStats($attempts);

        return [
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'category' => $exam->category,
                'total_points' => $exam->total_points,
                'passing_score' => $exam->passing_score,
            ],
            'total_attempts' => $totalAttempts,
            'pass_rate' => round(($passedAttempts / $totalAttempts) * 100, 2),
            'average_score' => round($averageScore, 2),
            'average_percentage' => round($averagePercentage, 2),
            'completion_rate' => 100,
            'question_stats' => $questionStats,
            'topic_breakdown' => $topicBreakdown,
            'year_level_stats' => $yearLevelStats,
        ];
    }

    /**
     * Calculate question-level statistics.
     */
    private function calculateQuestionStats($attempts, $exam): array
    {
        $questionStats = [];

        foreach ($exam->questions as $question) {
            $correctCount = 0;
            $totalAnswers = 0;

            foreach ($attempts as $attempt) {
                $answer = $attempt->answers()->where('question_id', $question->id)->first();
                if ($answer) {
                    $totalAnswers++;
                    if ($answer->is_correct) {
                        $correctCount++;
                    }
                }
            }

            $successRate = $totalAnswers > 0 ? ($correctCount / $totalAnswers) * 100 : 0;

            // Handle topic differently for institution vs national
            $topicName = 'No Topic';
            if ($exam instanceof InstitutionAssessment) {
                $topicName = $question->topic?->name ?? 'No Topic';
            } elseif ($exam instanceof NationalAssessment) {
                $topicName = $question->topic ?? 'No Topic';
            }

            $questionStats[] = [
                'question_id' => $question->id,
                'question_text' => $question->question_text,
                'topic' => $topicName,
                'points' => $question->points,
                'times_answered' => $totalAnswers,
                'times_correct' => $correctCount,
                'success_rate' => round($successRate, 2),
            ];
        }

        return $questionStats;
    }

    /**
     * Calculate topic breakdown.
     */
    private function calculateTopicBreakdown($attempts, $exam): array
    {
        $topicStats = [];

        foreach ($exam->questions as $question) {
            // Handle topic differently for institution vs national
            $topicName = 'No Topic';
            if ($exam instanceof InstitutionAssessment) {
                $topicName = $question->topic?->name ?? 'No Topic';
            } elseif ($exam instanceof NationalAssessment) {
                $topicName = $question->topic ?? 'No Topic';
            }

            if (! isset($topicStats[$topicName])) {
                $topicStats[$topicName] = [
                    'topic' => $topicName,
                    'question_count' => 0,
                    'total_points' => 0,
                    'correct_answers' => 0,
                    'total_answers' => 0,
                ];
            }

            $topicStats[$topicName]['question_count']++;
            $topicStats[$topicName]['total_points'] += $question->points;

            foreach ($attempts as $attempt) {
                $answer = $attempt->answers()->where('question_id', $question->id)->first();
                if ($answer) {
                    $topicStats[$topicName]['total_answers']++;
                    if ($answer->is_correct) {
                        $topicStats[$topicName]['correct_answers']++;
                    }
                }
            }
        }

        // Calculate success rates
        foreach ($topicStats as &$stats) {
            $stats['success_rate'] = $stats['total_answers'] > 0
                ? round(($stats['correct_answers'] / $stats['total_answers']) * 100, 2)
                : 0;
        }

        return array_values($topicStats);
    }

    /**
     * Calculate year level statistics.
     */
    private function calculateYearLevelStats($attempts): array
    {
        $yearLevelStats = [];

        foreach ($attempts as $attempt) {
            $yearLevel = $attempt->year_level ?? 'Unknown';
            if (! isset($yearLevelStats[$yearLevel])) {
                $yearLevelStats[$yearLevel] = [
                    'year_level' => $yearLevel,
                    'count' => 0,
                    'total_score' => 0,
                    'passed' => 0,
                ];
            }

            $yearLevelStats[$yearLevel]['count']++;
            $yearLevelStats[$yearLevel]['total_score'] += $attempt->score;

            // Check if passed (need to get passing score from assessment)
            $assessment = $attempt->assessment;
            if ($assessment && $attempt->score >= $assessment->passing_score) {
                $yearLevelStats[$yearLevel]['passed']++;
            }
        }

        // Calculate averages and pass rates
        foreach ($yearLevelStats as &$stats) {
            $stats['average_score'] = round($stats['total_score'] / $stats['count'], 2);
            $stats['pass_rate'] = round(($stats['passed'] / $stats['count']) * 100, 2);
            unset($stats['total_score']);
        }

        return array_values($yearLevelStats);
    }

    /**
     * Placeholder methods for other analytics pages.
     */
    public function topicPerformance(Request $request): Response
    {
        return Inertia::render('analytics/topic-performance', []);
    }

    public function questionBank(Request $request): Response
    {
        return Inertia::render('analytics/question-bank', []);
    }

    public function categoryPerformance(Request $request): Response
    {
        return Inertia::render('analytics/category-performance', []);
    }

    public function trends(Request $request): Response
    {
        return Inertia::render('analytics/trends', []);
    }
}

