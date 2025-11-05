<?php

namespace App\Models\National;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NationalAnswer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'attempt_id',
        'question_id',
        'answer_data',
        'is_correct',
        'points_earned',
        'grader_feedback',
        'graded_by',
        'graded_at',
    ];

    protected function casts(): array
    {
        return [
            'answer_data' => 'array',
            'is_correct' => 'boolean',
            'points_earned' => 'decimal:2',
            'graded_at' => 'datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(NationalAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(NationalQuestion::class, 'question_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function autoGrade(): void
    {
        $question = $this->question;

        // Only auto-grade for certain question types
        if (! $question->isAutoGradable()) {
            return;
        }

        $isCorrect = false;
        $answerData = $this->answer_data;

        switch ($question->question_type) {
            case 'multiple_choice':
            case 'true_false':
                // Single correct answer
                $correctChoice = $question->choices()->where('is_correct', true)->first();
                $isCorrect = $answerData['choice_id'] ?? $correctChoice?->id == null;
                break;

            case 'multiple_select':
                // Multiple correct answers
                $correctChoiceIds = $question->choices()->where('is_correct', true)->pluck('id')->toArray();
                $selectedIds = $answerData['choice_ids'] ?? [];
                sort($correctChoiceIds);
                sort($selectedIds);
                $isCorrect = $correctChoiceIds === $selectedIds;
                break;

            case 'fill_blank':
                // Case-insensitive comparison
                $correctAnswer = strtolower(trim($answerData['correct_answer'] ?? ''));
                $userAnswer = strtolower(trim($answerData['answer'] ?? ''));
                $isCorrect = $correctAnswer === $userAnswer;
                break;
        }

        $this->update([
            'is_correct' => $isCorrect,
            'points_earned' => $isCorrect ? $question->points : 0,
        ]);
    }
}
