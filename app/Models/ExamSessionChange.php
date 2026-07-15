<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Institution\InstitutionAttempt;
use App\Models\National\NationalAttempt;

class ExamSessionChange extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_type',
        'attempt_id',
        'institution_attempt_id',
        'national_attempt_id',
        'user_id',
        'change_type',
        'previous_ip_address',
        'new_ip_address',
        'previous_user_agent',
        'new_user_agent',
        'browser_info',
        'detected_at',
    ];

    protected function casts(): array
    {
        return [
            'browser_info' => 'array',
            'detected_at' => 'datetime',
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
