<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'title',
        'description',
        'instructions',
        'assignment_type',
        'target_year_levels',
        'course_id',
        'max_score',
        'cme_credits',
        'credit_type',
        'due_date',
        'allow_late_submission',
        'late_submission_until',
        'late_penalty_percent',
        'allow_resubmission',
        'max_submissions',
        'allowed_file_types',
        'max_file_size_mb',
        'max_files',
        'is_published',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'target_year_levels' => 'array',
            'allowed_file_types' => 'array',
            'due_date' => 'datetime',
            'late_submission_until' => 'datetime',
            'allow_late_submission' => 'boolean',
            'allow_resubmission' => 'boolean',
            'is_published' => 'boolean',
            'cme_credits' => 'decimal:2',
            'credit_type' => 'string',
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

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function isAvailable(): bool
    {
        if (! $this->is_published) {
            return false;
        }

        return true;
    }

    public function isOverdue(): bool
    {
        if (! $this->due_date) {
            return false;
        }

        return now()->isAfter($this->due_date);
    }

    public function canStillSubmit(): bool
    {
        $now = now();

        // Check if assignment is published
        if (! $this->is_published) {
            return false;
        }

        // Check if regular due date hasn't passed
        if ($this->due_date && $now->isBefore($this->due_date)) {
            return true;
        }

        // Check if late submission is allowed
        if ($this->allow_late_submission && $this->late_submission_until) {
            return $now->isBefore($this->late_submission_until);
        }

        return false;
    }

    public function hasUserSubmitted(User $user): bool
    {
        return $this->submissions()
            ->where('user_id', $user->id)
            ->whereIn('status', ['submitted', 'graded', 'returned'])
            ->exists();
    }

    public function getUserSubmissionCount(User $user): int
    {
        return $this->submissions()
            ->where('user_id', $user->id)
            ->whereIn('status', ['submitted', 'graded', 'returned'])
            ->count();
    }
}
