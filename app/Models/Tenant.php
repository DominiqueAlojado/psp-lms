<?php

namespace App\Models;

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
}
