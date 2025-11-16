<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionBankStatistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'scope',
        'institution_id',
        'times_used_in_exams',
        'times_answered',
        'times_correct',
        'times_incorrect',
        'success_rate',
        'average_time_seconds',
        'computed_difficulty',
        'discrimination_index',
        'skip_count',
        'last_used_at',
        'statistics_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'times_used_in_exams' => 'integer',
            'times_answered' => 'integer',
            'times_correct' => 'integer',
            'times_incorrect' => 'integer',
            'success_rate' => 'decimal:2',
            'average_time_seconds' => 'decimal:2',
            'discrimination_index' => 'decimal:2',
            'skip_count' => 'integer',
            'last_used_at' => 'datetime',
            'statistics_updated_at' => 'datetime',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_id');
    }
}
