<?php

namespace App\Models;

use App\Models\Institution\InstitutionAssessment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class QuestionBank extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'question_bank';

    protected $fillable = [
        'organization_id',
        'topic_id',
        'created_by',
        'question_type',
        'question_text',
        'points',
        'explanation',
        'image_path',
        'difficulty_level',
        'times_used',
        'is_approved',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'times_used' => 'integer',
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Delete image when question is deleted
        static::deleting(function (QuestionBank $question) {
            if ($question->image_path && Storage::disk('public')->exists($question->image_path)) {
                Storage::disk('public')->delete($question->image_path);
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function choices(): HasMany
    {
        return $this->hasMany(QuestionBankChoice::class, 'question_id');
    }

    public function statistics(): HasOne
    {
        return $this->hasOne(QuestionBankStatistic::class, 'question_id');
    }

    public function assessments(): BelongsToMany
    {
        return $this->belongsToMany(
            InstitutionAssessment::class,
            'assessment_question_bank',
            'question_id',
            'assessment_id'
        )->withPivot(['order', 'points_override'])->withTimestamps();
    }

    /**
     * Increment usage counter when question is added to an exam.
     */
    public function incrementUsage(): void
    {
        $this->increment('times_used');
        $this->statistics()->update(['last_used_at' => now()]);
    }

    /**
     * Update statistics when exam is completed.
     */
    public function updateStatistics(bool $wasCorrect, ?float $timeSeconds = null): void
    {
        $stats = $this->statistics;

        $stats->increment('times_answered');

        if ($wasCorrect) {
            $stats->increment('times_correct');
        } else {
            $stats->increment('times_incorrect');
        }

        // Calculate success rate
        if ($stats->times_answered > 0) {
            $stats->success_rate = ($stats->times_correct / $stats->times_answered) * 100;
        }

        // Update average time
        if ($timeSeconds !== null) {
            if ($stats->average_time_seconds === null) {
                $stats->average_time_seconds = $timeSeconds;
            } else {
                // Running average
                $stats->average_time_seconds = (($stats->average_time_seconds * ($stats->times_answered - 1)) + $timeSeconds) / $stats->times_answered;
            }
        }

        // Compute difficulty based on success rate
        if ($stats->success_rate >= 70) {
            $stats->computed_difficulty = 'easy';
        } elseif ($stats->success_rate >= 40) {
            $stats->computed_difficulty = 'medium';
        } else {
            $stats->computed_difficulty = 'hard';
        }

        $stats->statistics_updated_at = now();
        $stats->save();
    }

    /**
     * Get difficulty badge color.
     */
    public function getDifficultyColorAttribute(): string
    {
        $difficulty = $this->statistics?->computed_difficulty ?? $this->difficulty_level;

        return match ($difficulty) {
            'easy' => 'green',
            'medium' => 'yellow',
            'hard' => 'red',
            default => 'gray',
        };
    }

    /**
     * Scope to get only approved questions.
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope to filter by organization.
     */
    public function scopeForOrganization($query, int $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }
}
