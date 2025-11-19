<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'event_registration_id',
        'user_id',
        'organization_id',
        'joined_at',
        'left_at',
        'last_seen_at',
        'duration_seconds',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'duration_seconds' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function eventRegistration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function calculateDuration(): void
    {
        if ($this->joined_at && $this->left_at) {
            $this->duration_seconds = $this->joined_at->diffInSeconds($this->left_at);
            $this->save();
        } elseif ($this->joined_at && $this->last_seen_at) {
            // Calculate duration based on last seen if not left yet
            $this->duration_seconds = $this->joined_at->diffInSeconds($this->last_seen_at);
            $this->save();
        }
    }

    public function markAsLeft(): void
    {
        $this->update([
            'left_at' => now(),
            'status' => 'left',
        ]);
        $this->calculateDuration();
    }

    public function updateLastSeen(): void
    {
        $this->update([
            'last_seen_at' => now(),
            'status' => 'active',
        ]);
        $this->calculateDuration();
    }
}
