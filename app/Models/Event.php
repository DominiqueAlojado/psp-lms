<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'title',
        'slug',
        'description',
        'event_category',
        'event_type',
        'start_date',
        'end_date',
        'registration_deadline',
        'location',
        'virtual_link',
        'capacity',
        'price',
        'is_free',
        'image_path',
        'cme_credits',
        'target_year_levels',
        'requirements',
        'requires_approval',
        'is_published',
        'speakers',
        'agenda_items',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'registration_deadline' => 'datetime',
            'target_year_levels' => 'array',
            'speakers' => 'array',
            'agenda_items' => 'array',
            'requires_approval' => 'boolean',
            'is_published' => 'boolean',
            'is_free' => 'boolean',
            'price' => 'decimal:2',
            'cme_credits' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Event $event) {
            if (empty($event->slug)) {
                $event->slug = Str::slug($event->title);
            }
        });

        static::updating(function (Event $event) {
            if ($event->isDirty('title')) {
                $event->slug = Str::slug($event->title);
            }
        });

        // Delete image when event is deleted
        static::deleting(function (Event $event) {
            if ($event->image_path && Storage::disk('public')->exists($event->image_path)) {
                Storage::disk('public')->delete($event->image_path);
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function isRegistrationOpen(): bool
    {
        if (! $this->is_published) {
            return false;
        }

        if ($this->registration_deadline && now()->isAfter($this->registration_deadline)) {
            return false;
        }

        // Check if event has started
        if (now()->isAfter($this->start_date)) {
            return false;
        }

        return true;
    }

    public function isFull(): bool
    {
        if (! $this->capacity) {
            return false;
        }

        $confirmedCount = $this->registrations()
            ->whereIn('registration_status', ['confirmed', 'approved'])
            ->count();

        return $confirmedCount >= $this->capacity;
    }

    public function getRemainingCapacity(): ?int
    {
        if (! $this->capacity) {
            return null;
        }

        $confirmedCount = $this->registrations()
            ->whereIn('registration_status', ['confirmed', 'approved'])
            ->count();

        return max(0, $this->capacity - $confirmedCount);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now());
    }

    public function scopeForOrganization($query, int $organizationId)
    {
        return $query->where(function ($q) use ($organizationId) {
            $q->where('organization_id', $organizationId)
                ->orWhereNull('organization_id'); // System-wide events
        });
    }
}
