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
        'owner_type',
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

    /**
     * Get all statistics for this question (one per scope/institution).
     */
    public function allStatistics(): HasMany
    {
        return $this->hasMany(QuestionBankStatistic::class, 'question_id');
    }

    /**
     * Get statistics for a specific scope and institution.
     */
    public function getStatisticsForScope(string $scope, ?int $institutionId = null): ?QuestionBankStatistic
    {
        return $this->allStatistics()
            ->where('scope', $scope)
            ->when($institutionId !== null, function ($q) use ($institutionId) {
                $q->where('institution_id', $institutionId);
            })
            ->when($institutionId === null, function ($q) {
                $q->whereNull('institution_id');
            })
            ->first();
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
    public function updateStatistics(bool $wasCorrect, ?float $timeSeconds = null, ?string $scope = null, ?int $institutionId = null): void
    {
        // Determine scope if not provided
        if ($scope === null) {
            $scope = $this->owner_type === 'national' ? 'national' : 'institution';
        }
        if ($institutionId === null && $scope === 'institution') {
            $institutionId = $this->organization_id;
        }

        // Get or create statistics for the specific scope
        $stats = $this->allStatistics()
            ->where('scope', $scope)
            ->when($institutionId !== null, function ($q) use ($institutionId) {
                $q->where('institution_id', $institutionId);
            })
            ->when($institutionId === null, function ($q) {
                $q->whereNull('institution_id');
            })
            ->first();

        // Create statistics if they don't exist
        if (! $stats) {
            $stats = $this->allStatistics()->create([
                'question_id' => $this->id,
                'scope' => $scope,
                'institution_id' => $institutionId,
                'times_used_in_exams' => 0,
                'times_answered' => 0,
                'times_correct' => 0,
                'times_incorrect' => 0,
                'success_rate' => 0,
            ]);
        }

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

    /**
     * Scope to filter by owner type (national or institution).
     */
    public function scopeForOwnerType($query, string $ownerType)
    {
        return $query->where('owner_type', $ownerType);
    }

    /**
     * Scope to get only national questions.
     */
    public function scopeNational($query)
    {
        return $query->where('owner_type', 'national');
    }

    /**
     * Scope to get only institution questions.
     */
    public function scopeInstitution($query)
    {
        return $query->where('owner_type', 'institution');
    }
}
