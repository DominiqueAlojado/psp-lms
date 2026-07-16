<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'organization_id',
        'user_id',
        'assigned_to_user_id',
        'title',
        'category',
        'priority',
        'status',
        'module_name',
        'page_url',
        'details',
        'resolved_at',
        'last_replied_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'last_replied_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class)->oldest();
    }

    public function latestMessage(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class)->latest();
    }
}
