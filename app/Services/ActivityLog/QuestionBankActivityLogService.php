<?php

namespace App\Services\ActivityLog;

use App\Models\QuestionBank;
use App\Models\Topic;
use Spatie\Activitylog\Models\Activity as ActivityLog;

class QuestionBankActivityLogService
{
    /**
     * Log question creation.
     */
    public function logQuestionCreated(QuestionBank $question, array $attributes = []): void
    {
        $topicName = $question->topic_id ? Topic::find($question->topic_id)?->name : 'None';
        
        activity()
            ->performedOn($question)
            ->causedBy(auth()->user() ?? null)
            ->useLog('question_bank')
            ->withProperties([
                'attributes' => $attributes ?: [
                    'question_text' => substr($question->question_text, 0, 100),
                    'question_type' => $question->question_type,
                    'topic' => $topicName,
                    'points' => $question->points,
                    'difficulty_level' => $question->difficulty_level,
                ],
            ])
            ->log('Question created');
    }

    /**
     * Log question update with consolidated changes.
     */
    public function logQuestionUpdated(
        QuestionBank $question,
        array $attributes = [],
        array $oldValues = []
    ): void {
        if (empty($attributes) && empty($oldValues)) {
            return;
        }

        activity()
            ->performedOn($question)
            ->causedBy(auth()->user() ?? null)
            ->useLog('question_bank')
            ->withProperties([
                'attributes' => $attributes,
                'old' => $oldValues,
            ])
            ->log('Question updated');
    }

    /**
     * Log question deletion.
     */
    public function logQuestionDeleted(QuestionBank $question): void
    {
        $topicName = $question->topic_id ? Topic::find($question->topic_id)?->name : 'None';
        
        activity()
            ->performedOn($question)
            ->causedBy(auth()->user() ?? null)
            ->useLog('question_bank')
            ->withProperties([
                'attributes' => [
                    'question_text' => substr($question->question_text, 0, 100),
                    'question_type' => $question->question_type,
                    'topic' => $topicName,
                ],
            ])
            ->log('Question deleted');
    }

    /**
     * Log question approval.
     */
    public function logQuestionApproved(QuestionBank $question): void
    {
        activity()
            ->performedOn($question)
            ->causedBy(auth()->user() ?? null)
            ->useLog('question_bank')
            ->withProperties([
                'attributes' => [
                    'is_approved' => true,
                    'question_text' => substr($question->question_text, 0, 100),
                ],
            ])
            ->log('Question approved');
    }

    /**
     * Get activity logs for a question.
     */
    public function getLogs(QuestionBank $question, int $limit = 100): array
    {
        $logs = ActivityLog::query()
            ->where('subject_type', QuestionBank::class)
            ->where('subject_id', $question->id)
            ->where('log_name', 'question_bank')
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
     * Build consolidated log data for question updates.
     */
    public function buildUpdateLogData(
        QuestionBank $question,
        array $validated,
        ?int $oldTopicId,
        string $oldQuestionType,
        string $oldQuestionText,
        int $oldPoints,
        ?string $oldExplanation,
        ?string $oldDifficultyLevel,
        bool $imageChanged,
        array $oldChoices = []
    ): array {
        $attributes = [];
        $oldValues = [];
        $hasChanges = false;

        // Check topic change
        if ($oldTopicId != ($validated['topic_id'] ?? null)) {
            // Fetch topic names for display
            $oldTopicName = $oldTopicId ? Topic::find($oldTopicId)?->name : 'None';
            $newTopicId = $validated['topic_id'] ?? null;
            $newTopicName = $newTopicId ? Topic::find($newTopicId)?->name : 'None';
            
            $attributes['topic'] = $newTopicName;
            $oldValues['topic'] = $oldTopicName;
            $hasChanges = true;
        }

        // Check question type change
        if ($oldQuestionType !== $validated['question_type']) {
            $attributes['question_type'] = $validated['question_type'];
            $oldValues['question_type'] = $oldQuestionType;
            $hasChanges = true;
        }

        // Check question text change
        if ($oldQuestionText !== $validated['question_text']) {
            $attributes['question_text'] = substr($validated['question_text'], 0, 100);
            $oldValues['question_text'] = substr($oldQuestionText, 0, 100);
            $hasChanges = true;
        }

        // Check points change
        $oldPointsInt = (int) $oldPoints;
        $newPointsInt = (int) ($validated['points'] ?? $oldPointsInt);
        if ($oldPointsInt !== $newPointsInt) {
            $attributes['points'] = $newPointsInt;
            $oldValues['points'] = $oldPointsInt;
            $hasChanges = true;
        }

        // Check explanation change
        if (($validated['explanation'] ?? null) !== $oldExplanation) {
            $attributes['explanation'] = $validated['explanation'] ?? null;
            $oldValues['explanation'] = $oldExplanation;
            $hasChanges = true;
        }

        // Check difficulty level change
        if (($validated['difficulty_level'] ?? null) !== $oldDifficultyLevel) {
            $attributes['difficulty_level'] = $validated['difficulty_level'] ?? null;
            $oldValues['difficulty_level'] = $oldDifficultyLevel;
            $hasChanges = true;
        }

        // Check image change
        if ($imageChanged) {
            $attributes['image'] = '***changed***';
            $oldValues['image'] = $question->image_path ? '***existing***' : '***none***';
            $hasChanges = true;
        }

        // Check choices changes
        if (! empty($oldChoices) && ! empty($validated['choices'])) {
            $oldCorrectChoices = collect($oldChoices)
                ->where('is_correct', true)
                ->pluck('choice_text')
                ->values()
                ->toArray();
            
            $newCorrectChoices = collect($validated['choices'])
                ->where('is_correct', true)
                ->pluck('choice_text')
                ->values()
                ->toArray();

            // Check if correct answer changed
            if (json_encode($oldCorrectChoices) !== json_encode($newCorrectChoices)) {
                $oldCorrectText = ! empty($oldCorrectChoices) 
                    ? implode(', ', array_map(fn($text) => substr($text, 0, 50), $oldCorrectChoices))
                    : 'None';
                $newCorrectText = ! empty($newCorrectChoices)
                    ? implode(', ', array_map(fn($text) => substr($text, 0, 50), $newCorrectChoices))
                    : 'None';
                
                $attributes['correct_answer'] = $newCorrectText;
                $oldValues['correct_answer'] = $oldCorrectText;
                $hasChanges = true;
            }

            // Check if choice texts changed (excluding correct answer changes)
            $oldChoiceTexts = collect($oldChoices)
                ->sortBy('order')
                ->pluck('choice_text')
                ->values()
                ->toArray();
            $newChoiceTexts = collect($validated['choices'])
                ->sortBy(function ($choice, $index) {
                    return $index; // Use array index as order
                })
                ->pluck('choice_text')
                ->values()
                ->toArray();

            if (json_encode($oldChoiceTexts) !== json_encode($newChoiceTexts)) {
                // Only log if it's not just a reordering
                $oldTextsStr = implode(' | ', array_map(fn($text) => substr($text, 0, 30), $oldChoiceTexts));
                $newTextsStr = implode(' | ', array_map(fn($text) => substr($text, 0, 30), $newChoiceTexts));
                
                if ($oldTextsStr !== $newTextsStr) {
                    $attributes['choices'] = $newTextsStr;
                    $oldValues['choices'] = $oldTextsStr;
                    $hasChanges = true;
                }
            }
        }

        return [
            'hasChanges' => $hasChanges,
            'attributes' => $attributes,
            'oldValues' => $oldValues,
        ];
    }
}

