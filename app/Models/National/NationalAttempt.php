<?php

namespace App\Models\National;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NationalAttempt extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'assessment_id',
        'user_id',
        'year_level',
        'organization_id',
        'started_at',
        'submitted_at',
        'score',
        'total_points',
        'national_rank',
        'institution_rank',
        'percentile',
        'status',
        'ip_address',
        'user_agent',
        'browser_metadata',
        'connection_type',
        'connection_speed',
        'ip_changes_count',
        'browser_changes_count',
        'last_activity_at',
        'total_idle_time',
        'idle_periods_count',
        'max_idle_duration',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'score' => 'decimal:2',
            'total_points' => 'integer',
            'national_rank' => 'integer',
            'institution_rank' => 'integer',
            'percentile' => 'decimal:2',
            'browser_metadata' => 'array',
            'connection_speed' => 'decimal:2',
            'ip_changes_count' => 'integer',
            'browser_changes_count' => 'integer',
            'last_activity_at' => 'datetime',
            'total_idle_time' => 'integer',
            'idle_periods_count' => 'integer',
            'max_idle_duration' => 'integer',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(NationalAssessment::class, 'assessment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(NationalAnswer::class, 'attempt_id');
    }

    public function calculateScore(): void
    {
        $totalEarned = $this->answers()->sum('points_earned');
        $this->update([
            'score' => $totalEarned,
            'status' => 'completed',
        ]);
    }

    public function getPercentageAttribute(): float
    {
        if ($this->total_points === 0) {
            return 0;
        }

        return ($this->score / $this->total_points) * 100;
    }

    public function isPassed(): bool
    {
        return $this->score >= $this->assessment->passing_score;
    }
}
