<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Institution\InstitutionAttempt;
use App\Models\National\NationalAttempt;

class ExamIdlePeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_type',
        'attempt_id',
        'institution_attempt_id',
        'national_attempt_id',
        'user_id',
        'started_at',
        'ended_at',
        'duration_seconds',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function institutionAttempt(): BelongsTo
    {
        return $this->belongsTo(InstitutionAttempt::class, 'institution_attempt_id');
    }

    public function nationalAttempt(): BelongsTo
    {
        return $this->belongsTo(NationalAttempt::class, 'national_attempt_id');
    }
}
