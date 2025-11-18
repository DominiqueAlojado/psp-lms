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
        $currentOrganization = $user->currentOrganization;
        $organizationId = $user->current_organization_id;
        $canViewAllOrganizations = $user->hasPermissionTo('view-all-assessment-reports');
        $isNational = $currentOrganization?->type === 'national';

        // Get exams based on current organization type
        $institutionExams = collect();
        $nationalExams = collect();

        if ($isNational) {
            // If current org is national, show only national exams
            $nationalExams = NationalAssessment::query()
                ->select('id', 'title', 'category', 'total_points', 'passing_score')
                ->where('is_published', true)
                ->orderBy('title')
                ->get();
        } else {
            // If current org is institution, show only institution exams
            $institutionExams = InstitutionAssessment::query()
                ->select('id', 'title', 'exam_category', 'total_points', 'passing_score')
                ->when(! $canViewAllOrganizations, function ($q) use ($organizationId) {
                    $q->where('organization_id', $organizationId);
                })
                ->where('is_published', true)
                ->orderBy('title')
                ->get();
        }

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
     * Display item analysis report for a specific exam.
     */
    public function itemAnalysis(Request $request): Response
    {
        $user = $request->user();
        $currentOrganization = $user->currentOrganization;
        $organizationId = $user->current_organization_id;
        $canViewAllOrganizations = $user->hasPermissionTo('view-all-assessment-reports');
        $isNational = $currentOrganization?->type === 'national';

        // Get exams based on current organization type
        $institutionExams = collect();
        $nationalExams = collect();

        if ($isNational) {
            // If current org is national, show only national exams
            $nationalExams = NationalAssessment::query()
                ->select('id', 'title', 'category', 'total_points', 'passing_score')
                ->where('is_published', true)
                ->orderBy('title')
                ->get();
        } else {
            // If current org is institution, show only institution exams
            $institutionExams = InstitutionAssessment::query()
                ->select('id', 'title', 'exam_category', 'total_points', 'passing_score')
                ->when(! $canViewAllOrganizations, function ($q) use ($organizationId) {
                    $q->where('organization_id', $organizationId);
                })
                ->where('is_published', true)
                ->orderBy('title')
                ->get();
        }

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

        // If exam is selected, calculate item analysis
        $itemAnalysis = null;
        if ($request->filled('exam')) {
            $examFilter = $request->input('exam');
            $itemAnalysis = $this->calculateItemAnalysis($examFilter, $organizationId, $canViewAllOrganizations, $request);
        }

        return Inertia::render('analytics/item-analysis', [
            'exams' => $exams,
            'organizations' => $organizations,
            'itemAnalysis' => $itemAnalysis,
            'filters' => [
                'exam' => $request->input('exam'),
                'organization' => $request->input('organization'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
            ],
        ]);
    }

    /**
     * Calculate item analysis for a specific exam.
     */
    private function calculateItemAnalysis(
        string $examFilter,
        ?int $organizationId,
        bool $canViewAllOrganizations,
        Request $request
    ): array {
        $isInstitution = str_starts_with($examFilter, 'institution_');
        $isNational = str_starts_with($examFilter, 'national_');

        if ($isInstitution) {
            $examId = (int) str_replace('institution_', '', $examFilter);
            return $this->calculateInstitutionItemAnalysis($examId, $organizationId, $canViewAllOrganizations, $request);
        } elseif ($isNational) {
            $examId = (int) str_replace('national_', '', $examFilter);
            return $this->calculateNationalItemAnalysis($examId, $canViewAllOrganizations, $request);
        }

        return [];
    }

    /**
     * Calculate item analysis for institution exam.
     */
    private function calculateInstitutionItemAnalysis(
        int $examId,
        ?int $organizationId,
        bool $canViewAllOrganizations,
        Request $request
    ): array {
        $exam = InstitutionAssessment::with(['questions.choices', 'questions.topic'])
            ->findOrFail($examId);

        $query = InstitutionAttempt::query()
            ->where('assessment_id', $examId)
            ->where('status', 'completed')
            ->with(['answers', 'user']);

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
                ],
                'total_attempts' => 0,
                'items' => [],
            ];
        }

        return $this->performItemAnalysis($attempts, $exam);
    }

    /**
     * Calculate item analysis for national exam.
     */
    private function calculateNationalItemAnalysis(
        int $examId,
        bool $canViewAllOrganizations,
        Request $request
    ): array {
        $exam = NationalAssessment::with(['questions.choices'])
            ->findOrFail($examId);

        $query = NationalAttempt::query()
            ->where('assessment_id', $examId)
            ->where('status', 'completed')
            ->with(['answers', 'user']);

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
                ],
                'total_attempts' => 0,
                'items' => [],
            ];
        }

        return $this->performItemAnalysis($attempts, $exam);
    }

    /**
     * Perform item analysis calculations.
     */
    private function performItemAnalysis($attempts, $exam): array
    {
        $totalAttempts = $attempts->count();
        $items = [];

        // Calculate total scores for discrimination
        $totalScores = $attempts->pluck('score')->toArray();
        sort($totalScores);
        $highGroupThreshold = (int) ceil($totalAttempts * 0.27); // Top 27%
        $lowGroupThreshold = (int) floor($totalAttempts * 0.27); // Bottom 27%
        $highGroupScores = array_slice($totalScores, -$highGroupThreshold);
        $lowGroupScores = array_slice($totalScores, 0, $lowGroupThreshold);
        $highGroupMin = $highGroupScores[0] ?? 0;
        $lowGroupMax = $lowGroupScores[count($lowGroupScores) - 1] ?? 0;

        foreach ($exam->questions as $question) {
            $correctCount = 0;
            $highGroupCorrect = 0;
            $lowGroupCorrect = 0;
            $highGroupCount = 0;
            $lowGroupCount = 0;
            $itemScores = [];
            $distractorAnalysis = [];

            // Initialize distractor analysis for each choice
            foreach ($question->choices as $choice) {
                $distractorAnalysis[$choice->id] = [
                    'choice_id' => $choice->id,
                    'choice_text' => $choice->choice_text,
                    'is_correct' => $choice->is_correct,
                    'count' => 0,
                    'percentage' => 0,
                ];
            }

            foreach ($attempts as $attempt) {
                $answer = $attempt->answers->firstWhere('question_id', $question->id);
                $isCorrect = $answer?->is_correct ?? false;
                $itemScore = $isCorrect ? $question->points : 0;
                $itemScores[] = $itemScore;

                if ($isCorrect) {
                    $correctCount++;
                }

                // Track distractor selection
                if ($answer && isset($answer->answer_data['choice_id'])) {
                    $choiceId = $answer->answer_data['choice_id'];
                    if (isset($distractorAnalysis[$choiceId])) {
                        $distractorAnalysis[$choiceId]['count']++;
                    }
                } elseif ($answer && isset($answer->answer_data['choice_ids'])) {
                    // For multiple select, count each selected choice
                    foreach ($answer->answer_data['choice_ids'] as $choiceId) {
                        if (isset($distractorAnalysis[$choiceId])) {
                            $distractorAnalysis[$choiceId]['count']++;
                        }
                    }
                }

                // Group for discrimination
                if ($attempt->score >= $highGroupMin) {
                    $highGroupCount++;
                    if ($isCorrect) {
                        $highGroupCorrect++;
                    }
                } elseif ($attempt->score <= $lowGroupMax) {
                    $lowGroupCount++;
                    if ($isCorrect) {
                        $lowGroupCorrect++;
                    }
                }
            }

            // Calculate metrics
            $difficultyIndex = $totalAttempts > 0 ? ($correctCount / $totalAttempts) : 0;
            $discriminationIndex = 0;
            if ($highGroupCount > 0 && $lowGroupCount > 0) {
                $highGroupProportion = $highGroupCorrect / $highGroupCount;
                $lowGroupProportion = $lowGroupCorrect / $lowGroupCount;
                $discriminationIndex = $highGroupProportion - $lowGroupProportion;
            }

            // Calculate point biserial correlation
            $pointBiserial = $this->calculatePointBiserial($itemScores, $totalScores);

            // Update distractor percentages
            foreach ($distractorAnalysis as &$distractor) {
                $distractor['percentage'] = $totalAttempts > 0
                    ? round(($distractor['count'] / $totalAttempts) * 100, 2)
                    : 0;
            }

            // Determine item quality
            $quality = $this->assessItemQuality($difficultyIndex, $discriminationIndex, $pointBiserial);

            // Handle topic differently for institution vs national
            $topicName = 'No Topic';
            if ($exam instanceof InstitutionAssessment) {
                $topicName = $question->topic?->name ?? 'No Topic';
            } elseif ($exam instanceof NationalAssessment) {
                $topicName = $question->topic ?? 'No Topic';
            }

            $items[] = [
                'question_id' => $question->id,
                'question_text' => $question->question_text,
                'question_type' => $question->question_type,
                'topic' => $topicName,
                'points' => $question->points,
                'order' => $question->order,
                'difficulty_index' => round($difficultyIndex, 3),
                'difficulty_label' => $this->getDifficultyLabel($difficultyIndex),
                'discrimination_index' => round($discriminationIndex, 3),
                'discrimination_label' => $this->getDiscriminationLabel($discriminationIndex),
                'point_biserial' => round($pointBiserial, 3),
                'correct_count' => $correctCount,
                'incorrect_count' => $totalAttempts - $correctCount,
                'total_responses' => $totalAttempts,
                'quality' => $quality,
                'distractor_analysis' => array_values($distractorAnalysis),
            ];
        }

        return [
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'category' => $exam instanceof InstitutionAssessment ? $exam->exam_category : $exam->category,
            ],
            'total_attempts' => $totalAttempts,
            'items' => $items,
        ];
    }

    /**
     * Calculate point biserial correlation.
     */
    private function calculatePointBiserial(array $itemScores, array $totalScores): float
    {
        if (count($itemScores) !== count($totalScores) || count($itemScores) < 2) {
            return 0;
        }

        $n = count($itemScores);
        $itemMean = array_sum($itemScores) / $n;
        $totalMean = array_sum($totalScores) / $n;

        $itemStd = sqrt(array_sum(array_map(fn ($x) => pow($x - $itemMean, 2), $itemScores)) / ($n - 1));
        $totalStd = sqrt(array_sum(array_map(fn ($x) => pow($x - $totalMean, 2), $totalScores)) / ($n - 1));

        if ($itemStd == 0 || $totalStd == 0) {
            return 0;
        }

        $covariance = 0;
        for ($i = 0; $i < $n; $i++) {
            $covariance += ($itemScores[$i] - $itemMean) * ($totalScores[$i] - $totalMean);
        }
        $covariance /= ($n - 1);

        return $covariance / ($itemStd * $totalStd);
    }

    /**
     * Get difficulty label.
     */
    private function getDifficultyLabel(float $difficulty): string
    {
        if ($difficulty >= 0.8) {
            return 'Very Easy';
        } elseif ($difficulty >= 0.6) {
            return 'Easy';
        } elseif ($difficulty >= 0.4) {
            return 'Moderate';
        } elseif ($difficulty >= 0.2) {
            return 'Difficult';
        } else {
            return 'Very Difficult';
        }
    }

    /**
     * Get discrimination label.
     */
    private function getDiscriminationLabel(float $discrimination): string
    {
        if ($discrimination >= 0.4) {
            return 'Excellent';
        } elseif ($discrimination >= 0.3) {
            return 'Good';
        } elseif ($discrimination >= 0.2) {
            return 'Fair';
        } elseif ($discrimination >= 0.1) {
            return 'Poor';
        } else {
            return 'Very Poor';
        }
    }

    /**
     * Assess overall item quality.
     */
    private function assessItemQuality(float $difficulty, float $discrimination, float $pointBiserial): string
    {
        // Good items: moderate difficulty (0.3-0.7) and good discrimination (>0.2)
        if ($difficulty >= 0.3 && $difficulty <= 0.7 && $discrimination >= 0.2) {
            return 'Good';
        }

        // Acceptable: moderate difficulty or good discrimination
        if (($difficulty >= 0.3 && $difficulty <= 0.7) || $discrimination >= 0.2) {
            return 'Acceptable';
        }

        // Needs review: too easy/difficult or poor discrimination
        if ($discrimination < 0.1) {
            return 'Needs Review';
        }

        return 'Marginal';
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

