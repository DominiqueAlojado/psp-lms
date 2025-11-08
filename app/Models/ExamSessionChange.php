<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSessionChange extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_type',
        'attempt_id',
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
}
