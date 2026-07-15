<?php

namespace App\Models\Institution;

use App\Models\ExamSessionChange;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InstitutionAttempt extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'assessment_id',
        'user_id',
        'year_level',
        'organization_id',
        'started_at',
        'submitted_at',
        'score',
        'total_points',
        'status',
        'active_session_id',
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
        return $this->belongsTo(InstitutionAssessment::class, 'assessment_id');
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
        return $this->hasMany(InstitutionAnswer::class, 'attempt_id');
    }

    public function sessionChanges(): HasMany
    {
        return $this->hasMany(ExamSessionChange::class, 'attempt_id')
            ->where('attempt_type', 'institution');
    }

    public function idlePeriods(): HasMany
    {
        return $this->hasMany(\App\Models\ExamIdlePeriod::class, 'attempt_id')
            ->where('attempt_type', 'institution');
    }

    protected static function boot(): void
    {
        parent::boot();

        // Cascade delete session changes and idle periods when attempt is deleted
        static::deleting(function (InstitutionAttempt $attempt) {
            $attempt->sessionChanges()->delete();
            $attempt->idlePeriods()->delete();
        });
    }

    public function calculateScore(): void
    {
        $totalEarned = $this->answers()
            ->whereHas('question', function ($query) {
                $query->where('assessment_id', $this->assessment_id);
            })
            ->sum('points_earned');

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['score', 'status', 'submitted_at'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'Institution exam attempt created',
                'updated' => 'Institution exam attempt updated',
                'deleted' => 'Institution exam attempt deleted',
                default => "Institution exam attempt {$eventName}",
            })
            ->useLogName('exam_attempts');
    }
}
