<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'organization_id',
        'registration_status',
        'payment_status',
        'payment_amount',
        'stripe_payment_intent_id',
        'stripe_customer_id',
        'payment_date',
        'checked_in_at',
        'custom_fields',
        'cancellation_reason',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'payment_amount' => 'decimal:2',
            'payment_date' => 'datetime',
            'checked_in_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'custom_fields' => 'array',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isConfirmed(): bool
    {
        return in_array($this->registration_status, ['confirmed', 'approved']);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid' || $this->payment_status === 'not_required';
    }

    public function isCheckedIn(): bool
    {
        return ! is_null($this->checked_in_at);
    }

    public function checkIn(): void
    {
        $this->update(['checked_in_at' => now()]);
    }

    public function cancel(?string $reason = null): void
    {
        $this->update([
            'registration_status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
        ]);
    }

    public function scopeConfirmed($query)
    {
        return $query->whereIn('registration_status', ['confirmed', 'approved']);
    }

    public function scopePending($query)
    {
        return $query->where('registration_status', 'pending');
    }
}
