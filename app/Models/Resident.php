<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Resident extends Model
{
    /** @use HasFactory<\Database\Factories\ResidentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'organization_id',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'contact_number',
        'course',
        'year_level',
        'status',
        'other_info',
    ];

    protected function casts(): array
    {
        return [
            'other_info' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Resident $resident) {
            if (empty($resident->uuid)) {
                $resident->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the organization that the resident belongs to.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user account associated with this resident.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(ResidentOrganizationMembership::class);
    }

    public function activeMemberships(): HasMany
    {
        return $this->memberships()->whereNull('ended_at');
    }

    /**
     * Get all organizations the resident is associated with (through their user account).
     */
    public function organizations()
    {
        return $this->user ? $this->user->organizations() : collect([]);
    }

    /**
     * Get active organizations the resident is associated with.
     */
    public function activeOrganizations()
    {
        return $this->user ? $this->user->organizations()->wherePivot('is_active', true) : collect([]);
    }

    /**
     * Add resident to an organization.
     */
    public function addToOrganization(Organization $organization, array $pivotData = []): bool
    {
        if (! $this->user) {
            return false;
        }

        $this->user->loadMissing('organizations');

        $existingOrganization = $this->user->organizations->firstWhere('id', $organization->id);

        if ($existingOrganization && ($existingOrganization->pivot->is_active ?? false)) {
            return false;
        }

        if ($existingOrganization) {
            $this->user->organizations()->updateExistingPivot($organization->id, array_merge([
                'is_active' => true,
            ], $pivotData));
        } else {
            $this->user->organizations()->attach($organization->id, array_merge([
                'joined_at' => now(),
                'is_active' => true,
            ], $pivotData));
        }

        $membership = $this->memberships()
            ->where('organization_id', $organization->id)
            ->orderByDesc('id')
            ->first();

        if ($membership) {
            $membership->update([
                'started_at' => $membership->started_at ?? now(),
                'ended_at' => null,
                'is_primary' => false,
                'year_level' => $this->year_level,
                'status' => $this->status,
            ]);
        } else {
            $this->memberships()->create([
                'organization_id' => $organization->id,
                'started_at' => now(),
                'ended_at' => null,
                'is_primary' => false,
                'year_level' => $this->year_level,
                'status' => $this->status,
            ]);
        }

        return true;
    }

    /**
     * Remove resident from an organization.
     */
    public function removeFromOrganization(Organization $organization): bool
    {
        if (! $this->user) {
            return false;
        }

        // Cannot remove from home organization
        if ($this->organization_id === $organization->id) {
            return false;
        }

        $this->user->organizations()->updateExistingPivot($organization->id, [
            'is_active' => false,
        ]);

        $this->memberships()
            ->where('organization_id', $organization->id)
            ->whereNull('ended_at')
            ->update([
                'ended_at' => now(),
                'is_primary' => false,
            ]);

        return true;
    }

    /**
     * Get the resident's full name.
     */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ]);

        return implode(' ', $parts);
    }

    /**
     * Get the resident's full name with middle initial.
     */
    public function getFullNameWithMiddleInitialAttribute(): string
    {
        $parts = [$this->first_name];

        if ($this->middle_name) {
            $parts[] = substr($this->middle_name, 0, 1).'.';
        }

        $parts[] = $this->last_name;

        return implode(' ', $parts);
    }

    /**
     * Scope to filter active residents.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter by year level.
     */
    public function scopeYearLevel($query, string $level)
    {
        return $query->where('year_level', $level);
    }

    /**
     * Scope to filter by organization.
     */
    public function scopeInOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope to search residents by name, email, or contact number.
     */
    public function scopeSearch($query, $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'ilike', "%{$search}%")
                ->orWhere('last_name', 'ilike', "%{$search}%")
                ->orWhere('email', 'ilike', "%{$search}%")
                ->orWhere('contact_number', 'ilike', "%{$search}%");
        });
    }
}
