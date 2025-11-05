<?php

namespace App\Models\National;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NationalQuestion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'assessment_id',
        'question_type',
        'question_text',
        'points',
        'explanation',
        'image_path',
        'difficulty_level',
        'topic',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'order' => 'integer',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(NationalAssessment::class, 'assessment_id');
    }

    public function choices(): HasMany
    {
        return $this->hasMany(NationalQuestionChoice::class, 'question_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(NationalAnswer::class, 'question_id');
    }

    public function requiresChoices(): bool
    {
        return in_array($this->question_type, ['multiple_choice', 'multiple_select', 'true_false']);
    }

    public function isAutoGradable(): bool
    {
        return in_array($this->question_type, ['multiple_choice', 'multiple_select', 'true_false', 'fill_blank']);
    }

    public function getAnswerStatistics(): array
    {
        if ($this->question_type !== 'multiple_choice') {
            return [];
        }

        $totalAnswers = $this->answers()->count();
        if ($totalAnswers === 0) {
            return [];
        }

        $stats = [];
        foreach ($this->choices as $choice) {
            $count = $this->answers()
                ->where('answer_data->choice_id', $choice->id)
                ->count();

            $stats[] = [
                'choice_id' => $choice->id,
                'choice_text' => $choice->choice_text,
                'is_correct' => $choice->is_correct,
                'count' => $count,
                'percentage' => round(($count / $totalAnswers) * 100, 2),
            ];
        }

        return $stats;
    }
}
