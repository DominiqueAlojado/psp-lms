<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Submission extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'assignment_id',
        'user_id',
        'organization_id',
        'year_level',
        'submission_text',
        'submitted_at',
        'score',
        'max_score',
        'status',
        'grader_feedback',
        'graded_by',
        'graded_at',
        'is_late',
        'late_days',
        'submission_number',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
            'score' => 'decimal:2',
            'is_late' => 'boolean',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(SubmissionFile::class);
    }

    public function getPercentageAttribute(): float
    {
        if ($this->max_score === 0) {
            return 0;
        }

        return ($this->score / $this->max_score) * 100;
    }

    public function isPassed(): bool
    {
        // Default passing is 60%
        return $this->percentage >= 60;
    }

    public function isGraded(): bool
    {
        return $this->status === 'graded';
    }

    public function calculateLateDays(): void
    {
        if (! $this->assignment->due_date || ! $this->submitted_at) {
            return;
        }

        $dueDate = $this->assignment->due_date;
        $submittedAt = $this->submitted_at;

        if ($submittedAt->isAfter($dueDate)) {
            $this->is_late = true;
            $this->late_days = $submittedAt->diffInDays($dueDate);
        } else {
            $this->is_late = false;
            $this->late_days = 0;
        }

        $this->save();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['score', 'status', 'grader_feedback', 'graded_by', 'graded_at'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'Submission created',
                'updated' => 'Submission updated',
                'deleted' => 'Submission deleted',
                default => "Submission {$eventName}",
            })
            ->useLogName('submissions');
    }
}
