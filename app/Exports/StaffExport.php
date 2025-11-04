<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class StaffExport extends BaseExport
{
    public function query(): Builder
    {
        // Get all roles except 'Resident'
        $staffRoleNames = Role::where('name', '!=', 'Resident')->pluck('name')->toArray();

        $query = User::query()
            ->whereHas('roles', function ($q) use ($staffRoleNames) {
                $q->whereIn('name', $staffRoleNames);
            })
            ->with(['roles', 'currentOrganization', 'organizations']);

        // Apply search filter
        if ($this->hasFilter('search')) {
            $search = $this->getFilter('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Apply role filter
        if ($this->hasFilter('role')) {
            $role = $this->getFilter('role');
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        // Apply organization filter
        if ($this->hasFilter('organization')) {
            $orgId = $this->getFilter('organization');
            $query->where('current_organization_id', $orgId);
        }

        return $query->orderBy('name', 'asc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'UUID',
            'Name',
            'Email',
            'Roles',
            'Primary Role',
            'Current Organization',
            'Total Organizations',
            'Email Verified',
            'Created At',
        ];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->uuid,
            $user->name,
            $user->email,
            $user->roles->pluck('name')->join(', '),
            $user->roles->first()?->name ?? 'N/A',
            $user->currentOrganization?->name ?? 'N/A',
            $user->organizations->count(),
            $user->email_verified_at ? 'Yes' : 'No',
            $user->created_at->format('Y-m-d H:i:s'),
        ];
    }
}

