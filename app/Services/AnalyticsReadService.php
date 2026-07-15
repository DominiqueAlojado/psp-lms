<?php

namespace App\Services;

use App\Models\Institution\InstitutionAssessment;
use App\Models\National\NationalAssessment;
use App\Repositories\Contracts\AnalyticsRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AnalyticsReadService
{
    public function __construct(
        private readonly AnalyticsRepositoryInterface $analyticsRepository,
    ) {}

    public function examAnalyticsPayload(Request $request): array
    {
        $user = $request->user();
        $currentOrganization = $user->currentOrganization;
        $organizationId = $user->current_organization_id;
        $canViewAllOrganizations = $user->hasPermissionTo('view-all-assessment-reports');
        $isNational = $currentOrganization?->type === 'national';
        $examFilter = $this->normalizeExamFilter($request->input('exam'));

        [$exams, $organizations] = $this->baseFilterData($organizationId, $canViewAllOrganizations, $isNational);

        $analytics = null;
        if ($examFilter !== null) {
            $analytics = $this->calculateExamAnalytics(
                $examFilter,
                $organizationId,
                $canViewAllOrganizations,
                $request
            );
        }

        return [
            'exams' => $exams,
            'organizations' => $organizations,
            'analytics' => $analytics,
            'filters' => [
                'exam' => $examFilter,
                'organization' => $request->input('organization'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
            ],
        ];
    }

    public function itemAnalysisPayload(Request $request): array
    {
        $user = $request->user();
        $currentOrganization = $user->currentOrganization;
        $organizationId = $user->current_organization_id;
        $canViewAllOrganizations = $user->hasPermissionTo('view-all-assessment-reports');
        $isNational = $currentOrganization?->type === 'national';
        $examFilter = $this->normalizeExamFilter($request->input('exam'));

        [$exams, $organizations] = $this->baseFilterData($organizationId, $canViewAllOrganizations, $isNational);

        $itemAnalysis = null;
        if ($examFilter !== null) {
            $itemAnalysis = $this->calculateItemAnalysis(
                $examFilter,
                $organizationId,
                $canViewAllOrganizations,
                $request
            );
        }

        return [
            'exams' => $exams,
            'organizations' => $organizations,
            'itemAnalysis' => $itemAnalysis,
            'filters' => [
                'exam' => $examFilter,
                'organization' => $request->input('organization'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
            ],
        ];
    }

    public function questionBankPayload(Request $request): array
    {
        $user = $request->user();
        $currentOrganization = $user->currentOrganization;
        $organizationId = $currentOrganization?->id;
        $isNational = $currentOrganization?->type === 'national';

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $questions = $this->analyticsRepository->paginateQuestionBankAnalytics(
            $organizationId,
            $isNational,
            $request->only(['search', 'topic', 'question_type', 'difficulty', 'approval_status', 'performance_filter']),
            $sortBy,
            $sortOrder
        );

        $questions->getCollection()->transform(function ($question) use ($isNational, $organizationId) {
            $statistics = $question->allStatistics
                ->where('scope', $isNational ? 'national' : 'institution')
                ->when(! $isNational, function ($collection) use ($organizationId) {
                    return $collection->where('institution_id', $organizationId);
                })
                ->when($isNational, function ($collection) {
                    return $collection->whereNull('institution_id');
                })
                ->first();

            $question->statistics = $statistics;

            return $question;
        });

        return [
            'questions' => $questions,
            'summary' => $this->analyticsRepository->getQuestionBankSummary($organizationId, $isNational),
            'topics' => $this->analyticsRepository->getQuestionBankTopics($organizationId, $isNational),
            'filters' => [
                'search' => $request->input('search'),
                'topic' => $request->input('topic'),
                'question_type' => $request->input('question_type'),
                'difficulty' => $request->input('difficulty'),
                'approval_status' => $request->input('approval_status'),
                'performance_filter' => $request->input('performance_filter'),
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
        ];
    }

    private function baseFilterData(?int $organizationId, bool $canViewAllOrganizations, bool $isNational): array
    {
        $institutionExams = collect();
        $nationalExams = collect();

        if ($isNational) {
            $nationalExams = $this->analyticsRepository->getPublishedNationalExams();
        } else {
            $institutionExams = $this->analyticsRepository->getPublishedInstitutionExams($organizationId, $canViewAllOrganizations);
        }

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

        $organizations = $canViewAllOrganizations
            ? $this->analyticsRepository->getActiveInstitutionOrganizations()
            : collect();

        return [$exams, $organizations];
    }

    private function calculateExamAnalytics(string $examFilter, ?int $organizationId, bool $canViewAllOrganizations, Request $request): array
    {
        if (str_starts_with($examFilter, 'institution_')) {
            return $this->calculateInstitutionExamAnalytics(
                (int) str_replace('institution_', '', $examFilter),
                $organizationId,
                $canViewAllOrganizations,
                $request
            );
        }

        if (str_starts_with($examFilter, 'national_')) {
            return $this->calculateNationalExamAnalytics(
                (int) str_replace('national_', '', $examFilter),
                $request
            );
        }

        return [];
    }

    private function calculateInstitutionExamAnalytics(int $examId, ?int $organizationId, bool $canViewAllOrganizations, Request $request): array
    {
        $exam = $this->analyticsRepository->findInstitutionAssessmentForAnalytics($examId);
        $attempts = $this->analyticsRepository->getCompletedInstitutionAttemptsForExam(
            $examId,
            $organizationId,
            $canViewAllOrganizations,
            $request->only(['organization', 'date_from', 'date_to'])
        );

        return $this->buildExamAnalyticsPayload($attempts, $exam, $exam->exam_category);
    }

    private function calculateNationalExamAnalytics(int $examId, Request $request): array
    {
        $exam = $this->analyticsRepository->findNationalAssessmentForAnalytics($examId);
        $attempts = $this->analyticsRepository->getCompletedNationalAttemptsForExam(
            $examId,
            $request->only(['date_from', 'date_to'])
        );

        return $this->buildExamAnalyticsPayload($attempts, $exam, $exam->category);
    }

    private function buildExamAnalyticsPayload(Collection $attempts, InstitutionAssessment|NationalAssessment $exam, string $category): array
    {
        if ($attempts->isEmpty()) {
            return [
                'exam' => [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'category' => $category,
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
        $passedAttempts = $attempts->filter(fn ($attempt) => $attempt->score >= $exam->passing_score)->count();
        $averageScore = $attempts->avg('score');
        $averagePercentage = $attempts->avg(fn ($attempt) => ($attempt->score / $exam->total_points) * 100);

        return [
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'category' => $category,
                'total_points' => $exam->total_points,
                'passing_score' => $exam->passing_score,
            ],
            'total_attempts' => $totalAttempts,
            'pass_rate' => round(($passedAttempts / $totalAttempts) * 100, 2),
            'average_score' => round($averageScore, 2),
            'average_percentage' => round($averagePercentage, 2),
            'completion_rate' => 100,
            'question_stats' => $this->calculateQuestionStats($attempts, $exam),
            'topic_breakdown' => $this->calculateTopicBreakdown($attempts, $exam),
            'year_level_stats' => $this->calculateYearLevelStats($attempts),
        ];
    }

    private function calculateQuestionStats(Collection $attempts, InstitutionAssessment|NationalAssessment $exam): array
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
            $topicName = $exam instanceof InstitutionAssessment
                ? $question->topic?->name ?? 'No Topic'
                : $question->topic ?? 'No Topic';

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

    private function calculateTopicBreakdown(Collection $attempts, InstitutionAssessment|NationalAssessment $exam): array
    {
        $topicStats = [];

        foreach ($exam->questions as $question) {
            $topicName = $exam instanceof InstitutionAssessment
                ? $question->topic?->name ?? 'No Topic'
                : $question->topic ?? 'No Topic';

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

        foreach ($topicStats as &$stats) {
            $stats['success_rate'] = $stats['total_answers'] > 0
                ? round(($stats['correct_answers'] / $stats['total_answers']) * 100, 2)
                : 0;
        }

        return array_values($topicStats);
    }

    private function calculateYearLevelStats(Collection $attempts): array
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

            $assessment = $attempt->assessment;
            if ($assessment && $attempt->score >= $assessment->passing_score) {
                $yearLevelStats[$yearLevel]['passed']++;
            }
        }

        foreach ($yearLevelStats as &$stats) {
            $stats['average_score'] = round($stats['total_score'] / $stats['count'], 2);
            $stats['pass_rate'] = round(($stats['passed'] / $stats['count']) * 100, 2);
            unset($stats['total_score']);
        }

        return array_values($yearLevelStats);
    }

    private function calculateItemAnalysis(string $examFilter, ?int $organizationId, bool $canViewAllOrganizations, Request $request): array
    {
        if (str_starts_with($examFilter, 'institution_')) {
            return $this->calculateInstitutionItemAnalysis(
                (int) str_replace('institution_', '', $examFilter),
                $organizationId,
                $canViewAllOrganizations,
                $request
            );
        }

        if (str_starts_with($examFilter, 'national_')) {
            return $this->calculateNationalItemAnalysis(
                (int) str_replace('national_', '', $examFilter),
                $request
            );
        }

        return [];
    }

    private function calculateInstitutionItemAnalysis(int $examId, ?int $organizationId, bool $canViewAllOrganizations, Request $request): array
    {
        $exam = $this->analyticsRepository->findInstitutionAssessmentForAnalytics($examId, true);
        $attempts = $this->analyticsRepository->getCompletedInstitutionAttemptsForExam(
            $examId,
            $organizationId,
            $canViewAllOrganizations,
            $request->only(['organization', 'date_from', 'date_to']),
            true
        );

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

    private function calculateNationalItemAnalysis(int $examId, Request $request): array
    {
        $exam = $this->analyticsRepository->findNationalAssessmentForAnalytics($examId, true);
        $attempts = $this->analyticsRepository->getCompletedNationalAttemptsForExam(
            $examId,
            $request->only(['date_from', 'date_to']),
            true
        );

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

    private function performItemAnalysis(Collection $attempts, InstitutionAssessment|NationalAssessment $exam): array
    {
        $totalAttempts = $attempts->count();
        $items = [];

        $totalScores = $attempts->pluck('score')->toArray();
        sort($totalScores);
        $highGroupThreshold = (int) ceil($totalAttempts * 0.27);
        $lowGroupThreshold = (int) floor($totalAttempts * 0.27);
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

                if ($answer && isset($answer->answer_data['choice_id'])) {
                    $choiceId = $answer->answer_data['choice_id'];
                    if (isset($distractorAnalysis[$choiceId])) {
                        $distractorAnalysis[$choiceId]['count']++;
                    }
                } elseif ($answer && isset($answer->answer_data['choice_ids'])) {
                    foreach ($answer->answer_data['choice_ids'] as $choiceId) {
                        if (isset($distractorAnalysis[$choiceId])) {
                            $distractorAnalysis[$choiceId]['count']++;
                        }
                    }
                }

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

            $difficultyIndex = $totalAttempts > 0 ? ($correctCount / $totalAttempts) : 0;
            $discriminationIndex = 0;
            if ($highGroupCount > 0 && $lowGroupCount > 0) {
                $discriminationIndex = ($highGroupCorrect / $highGroupCount) - ($lowGroupCorrect / $lowGroupCount);
            }

            $pointBiserial = $this->calculatePointBiserial($itemScores, $totalScores);

            foreach ($distractorAnalysis as &$distractor) {
                $distractor['percentage'] = $totalAttempts > 0
                    ? round(($distractor['count'] / $totalAttempts) * 100, 2)
                    : 0;
            }

            $topicName = $exam instanceof InstitutionAssessment
                ? $question->topic?->name ?? 'No Topic'
                : $question->topic ?? 'No Topic';

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
                'quality' => $this->assessItemQuality($difficultyIndex, $discriminationIndex, $pointBiserial),
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

    private function calculatePointBiserial(array $itemScores, array $totalScores): float
    {
        if (count($itemScores) !== count($totalScores) || count($itemScores) < 2) {
            return 0;
        }

        $n = count($itemScores);
        $itemMean = array_sum($itemScores) / $n;
        $totalMean = array_sum($totalScores) / $n;
        $itemStd = sqrt(array_sum(array_map(fn ($x) => ($x - $itemMean) ** 2, $itemScores)) / ($n - 1));
        $totalStd = sqrt(array_sum(array_map(fn ($x) => ($x - $totalMean) ** 2, $totalScores)) / ($n - 1));

        if ($itemStd == 0 || $totalStd == 0) {
            return 0;
        }

        $covariance = 0;
        for ($i = 0; $i < $n; $i++) {
            $covariance += ($itemScores[$i] - $itemMean) * ($totalScores[$i] - $totalMean);
        }

        return ($covariance / ($n - 1)) / ($itemStd * $totalStd);
    }

    private function getDifficultyLabel(float $difficulty): string
    {
        return match (true) {
            $difficulty >= 0.8 => 'Very Easy',
            $difficulty >= 0.6 => 'Easy',
            $difficulty >= 0.4 => 'Moderate',
            $difficulty >= 0.2 => 'Difficult',
            default => 'Very Difficult',
        };
    }

    private function getDiscriminationLabel(float $discrimination): string
    {
        return match (true) {
            $discrimination >= 0.4 => 'Excellent',
            $discrimination >= 0.3 => 'Good',
            $discrimination >= 0.2 => 'Fair',
            $discrimination >= 0.1 => 'Poor',
            default => 'Very Poor',
        };
    }

    private function assessItemQuality(float $difficulty, float $discrimination, float $pointBiserial): string
    {
        if ($difficulty >= 0.3 && $difficulty <= 0.7 && $discrimination >= 0.2) {
            return 'Good';
        }

        if (($difficulty >= 0.3 && $difficulty <= 0.7) || $discrimination >= 0.2) {
            return 'Acceptable';
        }

        if ($discrimination < 0.1) {
            return 'Needs Review';
        }

        return 'Marginal';
    }

    private function normalizeExamFilter(?string $examFilter): ?string
    {
        if ($examFilter === null) {
            return null;
        }

        $examFilter = trim($examFilter);

        if ($examFilter === '' || $examFilter === 'all') {
            return null;
        }

        return $examFilter;
    }
}
