<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
}
