<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Multitenancy\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    /**
     * Scope a query to only include tenants matching the given domain.
     */
    public function scopeWhereDomain($query, string $domain)
    {
        return $query->where('domain', $domain);
    }

    /**
     * Get the residents (users) that belong to this hospital.
     */
    public function residents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->withTimestamps();
    }

    /**
     * Get the resident count for this hospital.
     */
    public function getResidentCountAttribute(): int
    {
        return $this->residents()->count();
    }
}
