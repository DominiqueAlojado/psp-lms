<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    /** @use HasFactory<\Database\Factories\AnnouncementFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'created_by',
        'title',
        'content',
        'scope',
        'priority',
        'is_published',
        'is_pinned',
        'target_year_levels',
        'expires_at',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'target_year_levels' => 'array',
            'is_published' => 'boolean',
            'is_pinned' => 'boolean',
            'expires_at' => 'datetime',
            'views_count' => 'integer',
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

    public function viewers()
    {
        return $this->belongsToMany(User::class, 'announcement_views')
            ->withPivot('viewed_at');
    }

    /**
     * Scope to get active (published and not expired) announcements.
     */
    public function scopeActive($query)
    {
        return $query->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to get announcements visible to a specific organization.
     */
    public function scopeVisibleTo($query, int $organizationId)
    {
        return $query->where(function ($q) use ($organizationId) {
            $q->where('scope', 'system')
                ->orWhere(function ($subQ) use ($organizationId) {
                    $subQ->where('scope', 'organization')
                        ->where('organization_id', $organizationId);
                });
        });
    }

    /**
     * Check if announcement is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if announcement is system-wide.
     */
    public function isSystemWide(): bool
    {
        return $this->scope === 'system';
    }

    /**
     * Mark announcement as viewed by a user.
     */
    public function markAsViewedBy(int $userId): void
    {
        // Check if already viewed
        if (! $this->viewers()->where('user_id', $userId)->exists()) {
            $this->viewers()->attach($userId, ['viewed_at' => now()]);
            $this->increment('views_count');
        }
    }

    /**
     * Check if user has viewed this announcement.
     */
    public function hasBeenViewedBy(int $userId): bool
    {
        return $this->viewers()->where('user_id', $userId)->exists();
    }
}
