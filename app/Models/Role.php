<?php

namespace App\Models;

use Spatie\Permission\Models\Role as BaseRole;

class Role extends BaseRole
{
    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Scope queries to current tenant (only for tenant-specific roles)
        // System admin roles (tenant_id = null) are accessible globally
        static::addGlobalScope('tenant', function ($query) {
            $tenant = Tenant::current();

            if ($tenant) {
                // Show roles that belong to current tenant OR system admin roles (null tenant_id)
                $query->where(function ($q) use ($tenant) {
                    $q->where('tenant_id', $tenant->id)
                        ->orWhereNull('tenant_id');
                });
            } else {
                // If no tenant, only show system admin roles
                $query->whereNull('tenant_id');
            }
        });
    }

    /**
     * Check if this is a system admin role (global, not tenant-specific).
     */
    public function isSystemAdmin(): bool
    {
        return $this->tenant_id === null;
    }

    /**
     * Get the tenant (hospital) that owns this role.
     */
    public function tenant()
    {
        if ($this->isSystemAdmin()) {
            return null;
        }

        return Tenant::on('landlord')->find($this->tenant_id);
    }
}
