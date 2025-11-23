<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmeCredit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'organization_id',
        'source_type',
        'source_id',
        'credit_type',
        'credits',
        'description',
        'status',
        'earned_at',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'credits' => 'decimal:2',
            'earned_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Get the user who earned the credit.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the organization context.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who approved the credit.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the source event (if source_type is 'event').
     */
    public function event()
    {
        if ($this->source_type === 'event') {
            return $this->belongsTo(Event::class, 'source_id');
        }

        return null;
    }

    /**
     * Get the source attempt (if source_type is 'exam_institution' or 'exam_national').
     */
    public function attempt()
    {
        if ($this->source_type === 'exam_institution') {
            return $this->belongsTo(\App\Models\Institution\InstitutionAttempt::class, 'source_id');
        }

        if ($this->source_type === 'exam_national') {
            return $this->belongsTo(\App\Models\National\NationalAttempt::class, 'source_id');
        }

        return null;
    }

    /**
     * Get the source assignment (if source_type is 'assignment_submission').
     */
    public function assignment()
    {
        if ($this->source_type === 'assignment_submission') {
            return $this->belongsTo(Assignment::class, 'source_id');
        }

        return null;
    }

    /**
     * Scope to get approved credits only.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get credits for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get credits within a date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('earned_at', [$startDate, $endDate]);
    }

    /**
     * Get total credits for a user.
     */
    public static function getTotalCreditsForUser(int $userId, ?string $startDate = null, ?string $endDate = null, ?string $creditType = null): float
    {
        $query = static::where('user_id', $userId)
            ->where('status', 'approved');

        if ($creditType) {
            $query->where('credit_type', $creditType);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('earned_at', [$startDate, $endDate]);
        }

        return (float) $query->sum('credits');
    }

    /**
     * Award CME/CPD credits to a user.
     */
    public static function award(
        int $userId,
        string $sourceType,
        int $sourceId,
        float $credits,
        ?string $description = null,
        ?int $organizationId = null,
        ?int $approvedBy = null,
        string $creditType = 'cme'
    ): self {
        return static::create([
            'user_id' => $userId,
            'organization_id' => $organizationId,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'credit_type' => $creditType,
            'credits' => $credits,
            'description' => $description,
            'status' => $approvedBy ? 'approved' : 'pending',
            'earned_at' => now(),
            'approved_by' => $approvedBy,
            'approved_at' => $approvedBy ? now() : null,
        ]);
    }
}
