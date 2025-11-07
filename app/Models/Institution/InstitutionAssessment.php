<?php

namespace App\Models\Institution;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstitutionAssessment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'title',
        'description',
        'exam_category',
        'course_id',
        'duration_minutes',
        'total_points',
        'passing_score',
        'randomize_questions',
        'randomize_choices',
        'show_results_immediately',
        'allow_review',
        'is_published',
        'available_from',
        'available_until',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'randomize_questions' => 'boolean',
            'randomize_choices' => 'boolean',
            'show_results_immediately' => 'boolean',
            'allow_review' => 'boolean',
            'is_published' => 'boolean',
            'available_from' => 'datetime',
            'available_until' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(InstitutionQuestion::class, 'assessment_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(InstitutionAttempt::class, 'assessment_id');
    }

    public function isAvailable(): bool
    {
        if (! $this->is_published) {
            return false;
        }

        $now = now();

        if ($this->available_from && $now->isBefore($this->available_from)) {
            return false;
        }

        if ($this->available_until && $now->isAfter($this->available_until)) {
            return false;
        }

        return true;
    }

    public function hasUserAttempted(User $user): bool
    {
        return $this->attempts()
            ->where('user_id', $user->id)
            ->whereIn('status', ['completed', 'graded'])
            ->exists();
    }
}
