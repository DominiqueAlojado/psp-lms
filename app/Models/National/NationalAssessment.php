<?php

namespace App\Models\National;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NationalAssessment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'exam_year',
        'exam_period',
        'category',
        'duration_minutes',
        'total_points',
        'passing_score',
        'randomize_questions',
        'randomize_choices',
        'show_results_immediately',
        'allow_review',
        'is_published',
        'national_ranking_enabled',
        'institution_comparison_enabled',
        'scheduled_date',
        'results_release_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'exam_year' => 'integer',
            'category' => 'string',
            'randomize_questions' => 'boolean',
            'randomize_choices' => 'boolean',
            'show_results_immediately' => 'boolean',
            'allow_review' => 'boolean',
            'is_published' => 'boolean',
            'national_ranking_enabled' => 'boolean',
            'institution_comparison_enabled' => 'boolean',
            'scheduled_date' => 'datetime',
            'results_release_date' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(NationalQuestion::class, 'assessment_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(NationalAttempt::class, 'assessment_id');
    }

    public function isAvailable(): bool
    {
        if (! $this->is_published) {
            return false;
        }

        $now = now();

        if ($this->scheduled_date && $now->isBefore($this->scheduled_date)) {
            return false;
        }

        return true;
    }

    public function canViewResults(): bool
    {
        if ($this->show_results_immediately) {
            return true;
        }

        if ($this->results_release_date) {
            return now()->isAfter($this->results_release_date);
        }

        return false;
    }

    public function hasUserAttempted(User $user): bool
    {
        return $this->attempts()
            ->where('user_id', $user->id)
            ->whereIn('status', ['completed', 'graded'])
            ->exists();
    }

    public function calculateNationalRankings(): void
    {
        // Get all completed attempts ordered by score
        $attempts = $this->attempts()
            ->where('status', 'completed')
            ->orderByDesc('score')
            ->orderBy('submitted_at')
            ->get();

        // Assign national ranks
        foreach ($attempts as $index => $attempt) {
            $attempt->update(['national_rank' => $index + 1]);
        }
    }

    public function calculateInstitutionRankings(): void
    {
        // Get all unique organizations
        $organizationIds = $this->attempts()
            ->where('status', 'completed')
            ->distinct()
            ->pluck('organization_id');

        // Calculate rank within each institution
        foreach ($organizationIds as $orgId) {
            $attempts = $this->attempts()
                ->where('organization_id', $orgId)
                ->where('status', 'completed')
                ->orderByDesc('score')
                ->orderBy('submitted_at')
                ->get();

            foreach ($attempts as $index => $attempt) {
                $attempt->update(['institution_rank' => $index + 1]);
            }
        }
    }
}
