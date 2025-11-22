<?php

namespace App\Services\ActivityLog;

use App\Models\National\NationalAssessment;
use Spatie\Activitylog\Models\Activity as ActivityLog;

class NationalAssessmentActivityLogService
{
    /**
     * Log assessment creation.
     */
    public function logAssessmentCreated(NationalAssessment $assessment, array $attributes = []): void
    {
        activity()
            ->performedOn($assessment)
            ->causedBy(auth()->user() ?? null)
            ->useLog('national_assessment')
            ->withProperties([
                'attributes' => $attributes ?: [
                    'title' => $assessment->title,
                    'exam_year' => $assessment->exam_year,
                    'exam_period' => $assessment->exam_period,
                    'category' => $assessment->category,
                    'passing_score' => $assessment->passing_score,
                    'duration_minutes' => $assessment->duration_minutes,
                    'is_published' => $assessment->is_published,
                ],
            ])
            ->log('National assessment created');
    }

    /**
     * Log assessment update with consolidated changes.
     */
    public function logAssessmentUpdated(
        NationalAssessment $assessment,
        array $attributes = [],
        array $oldValues = []
    ): void {
        if (empty($attributes) && empty($oldValues)) {
            return;
        }

        activity()
            ->performedOn($assessment)
            ->causedBy(auth()->user() ?? null)
            ->useLog('national_assessment')
            ->withProperties([
                'attributes' => $attributes,
                'old' => $oldValues,
            ])
            ->log('National assessment updated');
    }

    /**
     * Log assessment deletion.
     */
    public function logAssessmentDeleted(NationalAssessment $assessment): void
    {
        activity()
            ->performedOn($assessment)
            ->causedBy(auth()->user() ?? null)
            ->useLog('national_assessment')
            ->withProperties([
                'attributes' => [
                    'title' => $assessment->title,
                    'exam_year' => $assessment->exam_year,
                    'exam_period' => $assessment->exam_period,
                ],
            ])
            ->log('National assessment deleted');
    }

    /**
     * Log assessment duplication.
     */
    public function logAssessmentDuplicated(NationalAssessment $original, NationalAssessment $duplicate): void
    {
        activity()
            ->performedOn($duplicate)
            ->causedBy(auth()->user() ?? null)
            ->useLog('national_assessment')
            ->withProperties([
                'attributes' => [
                    'title' => $duplicate->title,
                    'original_id' => $original->id,
                    'original_title' => $original->title,
                ],
            ])
            ->log('National assessment duplicated');
    }

    /**
     * Log questions added to assessment.
     */
    public function logQuestionsAdded(
        NationalAssessment $assessment,
        int $questionsCount,
        int $totalPointsAdded
    ): void {
        activity()
            ->performedOn($assessment)
            ->causedBy(auth()->user() ?? null)
            ->useLog('national_assessment')
            ->withProperties([
                'attributes' => [
                    'questions_added' => $questionsCount,
                    'total_points_added' => $totalPointsAdded,
                ],
            ])
            ->log('Questions added to assessment');
    }

    /**
     * Log single question added to assessment.
     */
    public function logQuestionAdded(
        NationalAssessment $assessment,
        int $questionId,
        string $questionText,
        string $questionType,
        int $points,
        ?string $topic = null,
        array $choices = []
    ): void {
        // Format choices for display
        $choicesFormatted = [];
        foreach ($choices as $choice) {
            $text = substr(strip_tags($choice['choice_text'] ?? ''), 0, 50);
            $isCorrect = $choice['is_correct'] ?? false;
            $choicesFormatted[] = $isCorrect ? "{$text} (Correct)" : $text;
        }

        $correctAnswer = collect($choices)
            ->where('is_correct', true)
            ->pluck('choice_text')
            ->map(fn($text) => substr(strip_tags($text), 0, 50))
            ->values()
            ->toArray();

        activity()
            ->performedOn($assessment)
            ->causedBy(auth()->user() ?? null)
            ->useLog('national_assessment')
            ->withProperties([
                'attributes' => [
                    'question_id' => $questionId,
                    'question_text' => substr(strip_tags($questionText), 0, 100),
                    'question_type' => $questionType,
                    'topic' => $topic ?? 'None',
                    'points' => $points,
                    'correct_answer' => !empty($correctAnswer) ? implode(', ', $correctAnswer) : 'None',
                    'choices' => !empty($choicesFormatted) ? implode(' | ', $choicesFormatted) : 'None',
                ],
            ])
            ->log('Question added to assessment');
    }

    /**
     * Log question added from question bank.
     */
    public function logQuestionAddedFromBank(
        NationalAssessment $assessment,
        int $questionId,
        string $questionText,
        string $questionType,
        int $points,
        ?string $topic = null,
        array $choices = [],
        int $bankQuestionId
    ): void {
        // Format choices for display
        $choicesFormatted = [];
        foreach ($choices as $choice) {
            $text = substr(strip_tags($choice['choice_text'] ?? ''), 0, 50);
            $isCorrect = $choice['is_correct'] ?? false;
            $choicesFormatted[] = $isCorrect ? "{$text} (Correct)" : $text;
        }

        $correctAnswer = collect($choices)
            ->where('is_correct', true)
            ->pluck('choice_text')
            ->map(fn($text) => substr(strip_tags($text), 0, 50))
            ->values()
            ->toArray();

        activity()
            ->performedOn($assessment)
            ->causedBy(auth()->user() ?? null)
            ->useLog('national_assessment')
            ->withProperties([
                'attributes' => [
                    'question_id' => $questionId,
                    'question_text' => substr(strip_tags($questionText), 0, 100),
                    'question_type' => $questionType,
                    'topic' => $topic ?? 'None',
                    'points' => $points,
                    'correct_answer' => !empty($correctAnswer) ? implode(', ', $correctAnswer) : 'None',
                    'choices' => !empty($choicesFormatted) ? implode(' | ', $choicesFormatted) : 'None',
                    'source' => 'question_bank',
                ],
            ])
            ->log('Question added from question bank');
    }

    /**
     * Log question updated in assessment.
     */
    public function logQuestionUpdated(
        NationalAssessment $assessment,
        int $questionId,
        string $newQuestionText,
        string $newQuestionType,
        int $newPoints,
        ?string $newTopic,
        array $newChoices,
        string $oldQuestionText,
        string $oldQuestionType,
        int $oldPoints,
        ?string $oldTopic,
        array $oldChoices
    ): void {
        $attributes = [];
        $oldValues = [];
        $hasChanges = false;

        // Check question text change
        if ($oldQuestionText !== $newQuestionText) {
            $attributes['question_text'] = substr(strip_tags($newQuestionText), 0, 100);
            $oldValues['question_text'] = substr(strip_tags($oldQuestionText), 0, 100);
            $hasChanges = true;
        }

        // Check question type change
        if ($oldQuestionType !== $newQuestionType) {
            $attributes['question_type'] = $newQuestionType;
            $oldValues['question_type'] = $oldQuestionType;
            $hasChanges = true;
        }

        // Check points change
        if ($oldPoints !== $newPoints) {
            $attributes['points'] = $newPoints;
            $oldValues['points'] = $oldPoints;
            $hasChanges = true;
        }

        // Check topic change
        $oldTopicName = $oldTopic ?? 'None';
        $newTopicName = $newTopic ?? 'None';
        if ($oldTopicName !== $newTopicName) {
            $attributes['topic'] = $newTopicName;
            $oldValues['topic'] = $oldTopicName;
            $hasChanges = true;
        }

        // Check correct answer change
        $oldCorrectAnswer = collect($oldChoices)
            ->where('is_correct', true)
            ->pluck('choice_text')
            ->map(fn($text) => substr(strip_tags($text), 0, 50))
            ->values()
            ->toArray();

        $newCorrectAnswer = collect($newChoices)
            ->where('is_correct', true)
            ->pluck('choice_text')
            ->map(fn($text) => substr(strip_tags($text), 0, 50))
            ->values()
            ->toArray();

        if (json_encode($oldCorrectAnswer) !== json_encode($newCorrectAnswer)) {
            $attributes['correct_answer'] = !empty($newCorrectAnswer) ? implode(', ', $newCorrectAnswer) : 'None';
            $oldValues['correct_answer'] = !empty($oldCorrectAnswer) ? implode(', ', $oldCorrectAnswer) : 'None';
            $hasChanges = true;
        }

        // Check choices change (if choices text changed)
        $oldChoicesText = collect($oldChoices)
            ->pluck('choice_text')
            ->map(fn($text) => substr(strip_tags($text), 0, 30))
            ->values()
            ->toArray();

        $newChoicesText = collect($newChoices)
            ->pluck('choice_text')
            ->map(fn($text) => substr(strip_tags($text), 0, 30))
            ->values()
            ->toArray();

        if (json_encode($oldChoicesText) !== json_encode($newChoicesText)) {
            $oldChoicesFormatted = collect($oldChoices)->map(function ($choice) {
                $text = substr(strip_tags($choice['choice_text'] ?? ''), 0, 30);
                return ($choice['is_correct'] ?? false) ? "{$text} (Correct)" : $text;
            })->toArray();

            $newChoicesFormatted = collect($newChoices)->map(function ($choice) {
                $text = substr(strip_tags($choice['choice_text'] ?? ''), 0, 30);
                return ($choice['is_correct'] ?? false) ? "{$text} (Correct)" : $text;
            })->toArray();

            $attributes['choices'] = implode(' | ', $newChoicesFormatted);
            $oldValues['choices'] = implode(' | ', $oldChoicesFormatted);
            $hasChanges = true;
        }

        if (!$hasChanges) {
            return; // No changes, don't log
        }

        // Always include question text and ID for context, even if they didn't change
        activity()
            ->performedOn($assessment)
            ->causedBy(auth()->user() ?? null)
            ->useLog('national_assessment')
            ->withProperties([
                'attributes' => array_merge([
                    'question_id' => $questionId,
                    'question_text' => substr(strip_tags($newQuestionText), 0, 100),
                ], $attributes),
                'old' => $oldValues,
            ])
            ->log('Question updated in assessment');
    }

    /**
     * Log questions imported to assessment.
     */
    public function logQuestionsImported(
        NationalAssessment $assessment,
        int $questionsCount,
        int $totalPointsAdded
    ): void {
        activity()
            ->performedOn($assessment)
            ->causedBy(auth()->user() ?? null)
            ->useLog('national_assessment')
            ->withProperties([
                'attributes' => [
                    'questions_imported' => $questionsCount,
                    'total_points_added' => $totalPointsAdded,
                ],
            ])
            ->log('Questions imported to assessment');
    }

    /**
     * Get activity logs for an assessment.
     */
    public function getLogs(NationalAssessment $assessment, int $limit = 100): array
    {
        $logs = ActivityLog::query()
            ->where('subject_type', NationalAssessment::class)
            ->where('subject_id', $assessment->id)
            ->where('log_name', 'national_assessment')
            ->with(['subject', 'causer'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'log_name' => $activity->log_name,
                    'event' => $activity->event,
                    'properties' => $activity->properties,
                    'causer' => $activity->causer ? [
                        'id' => $activity->causer->id,
                        'name' => $activity->causer->name,
                        'email' => $activity->causer->email,
                    ] : null,
                    'subject' => $activity->subject ? [
                        'id' => $activity->subject->id,
                        'type' => class_basename($activity->subject_type),
                    ] : null,
                    'created_at' => $activity->created_at->toISOString(),
                ];
            })
            ->toArray();

        return $logs;
    }

    /**
     * Build consolidated log data for assessment updates.
     */
    public function buildUpdateLogData(
        NationalAssessment $assessment,
        array $validated,
        string $oldTitle,
        ?string $oldDescription,
        int $oldExamYear,
        string $oldExamPeriod,
        ?string $oldCategory,
        ?int $oldDurationMinutes,
        int $oldPassingScore,
        bool $oldIsPublished,
        ?string $oldScheduledDate,
        ?string $oldResultsReleaseDate,
        bool $oldRandomizeQuestions = false,
        bool $oldRandomizeChoices = false,
        bool $oldShowResultsImmediately = false,
        bool $oldAllowReview = false
    ): array {
        $attributes = [];
        $oldValues = [];
        $hasChanges = false;

        // Check title change
        if ($oldTitle !== $validated['title']) {
            $attributes['title'] = $validated['title'];
            $oldValues['title'] = $oldTitle;
            $hasChanges = true;
        }

        // Check description change
        if (($validated['description'] ?? null) !== $oldDescription) {
            $attributes['description'] = $validated['description'] ?? null;
            $oldValues['description'] = $oldDescription;
            $hasChanges = true;
        }

        // Check exam year change
        if (($validated['exam_year'] ?? $oldExamYear) !== $oldExamYear) {
            $attributes['exam_year'] = $validated['exam_year'] ?? $oldExamYear;
            $oldValues['exam_year'] = $oldExamYear;
            $hasChanges = true;
        }

        // Check exam period change
        if (($validated['exam_period'] ?? $oldExamPeriod) !== $oldExamPeriod) {
            $attributes['exam_period'] = $validated['exam_period'] ?? $oldExamPeriod;
            $oldValues['exam_period'] = $oldExamPeriod;
            $hasChanges = true;
        }

        // Check category change
        $newCategory = $validated['category'] ?? $oldCategory;
        if ($newCategory !== $oldCategory) {
            $attributes['category'] = $newCategory;
            $oldValues['category'] = $oldCategory;
            $hasChanges = true;
        }

        // Check duration change
        if (($validated['duration_minutes'] ?? null) !== $oldDurationMinutes) {
            $attributes['duration_minutes'] = $validated['duration_minutes'] ?? null;
            $oldValues['duration_minutes'] = $oldDurationMinutes;
            $hasChanges = true;
        }

        // Check passing score change
        if (($validated['passing_score'] ?? $oldPassingScore) !== $oldPassingScore) {
            $attributes['passing_score'] = $validated['passing_score'] ?? $oldPassingScore;
            $oldValues['passing_score'] = $oldPassingScore;
            $hasChanges = true;
        }

        // Check published status change
        if (($validated['is_published'] ?? $oldIsPublished) !== $oldIsPublished) {
            $attributes['is_published'] = $validated['is_published'] ?? $oldIsPublished;
            $oldValues['is_published'] = $oldIsPublished;
            $hasChanges = true;
        }

        // Check scheduled date change
        $newScheduledDate = isset($validated['scheduled_date'])
            ? ($validated['scheduled_date'] ? date('Y-m-d H:i:s', strtotime($validated['scheduled_date'])) : null)
            : $oldScheduledDate;
        if ($newScheduledDate !== $oldScheduledDate) {
            $attributes['scheduled_date'] = $newScheduledDate;
            $oldValues['scheduled_date'] = $oldScheduledDate;
            $hasChanges = true;
        }

        // Check results release date change
        $newResultsReleaseDate = isset($validated['results_release_date'])
            ? ($validated['results_release_date'] ? date('Y-m-d H:i:s', strtotime($validated['results_release_date'])) : null)
            : $oldResultsReleaseDate;
        if ($newResultsReleaseDate !== $oldResultsReleaseDate) {
            $attributes['results_release_date'] = $newResultsReleaseDate;
            $oldValues['results_release_date'] = $oldResultsReleaseDate;
            $hasChanges = true;
        }

        // Check randomize questions change
        $newRandomizeQuestions = $validated['randomize_questions'] ?? $oldRandomizeQuestions;
        if ($newRandomizeQuestions !== $oldRandomizeQuestions) {
            $attributes['randomize_questions'] = $newRandomizeQuestions;
            $oldValues['randomize_questions'] = $oldRandomizeQuestions;
            $hasChanges = true;
        }

        // Check randomize choices change
        $newRandomizeChoices = $validated['randomize_choices'] ?? $oldRandomizeChoices;
        if ($newRandomizeChoices !== $oldRandomizeChoices) {
            $attributes['randomize_choices'] = $newRandomizeChoices;
            $oldValues['randomize_choices'] = $oldRandomizeChoices;
            $hasChanges = true;
        }

        // Check show results immediately change
        $newShowResultsImmediately = $validated['show_results_immediately'] ?? $oldShowResultsImmediately;
        if ($newShowResultsImmediately !== $oldShowResultsImmediately) {
            $attributes['show_results_immediately'] = $newShowResultsImmediately;
            $oldValues['show_results_immediately'] = $oldShowResultsImmediately;
            $hasChanges = true;
        }

        // Check allow review change
        $newAllowReview = $validated['allow_review'] ?? $oldAllowReview;
        if ($newAllowReview !== $oldAllowReview) {
            $attributes['allow_review'] = $newAllowReview;
            $oldValues['allow_review'] = $oldAllowReview;
            $hasChanges = true;
        }

        return [
            'hasChanges' => $hasChanges,
            'attributes' => $attributes,
            'oldValues' => $oldValues,
        ];
    }
}
