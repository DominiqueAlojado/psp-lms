<?php

namespace App\Exports;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;

class InstitutionsExport extends BaseExport
{
    public function query(): Builder
    {
        $query = Organization::query()
            ->withCount(['residents', 'users']);

        // Apply search filter
        if ($this->hasFilter('search')) {
            $search = $this->getFilter('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        // Apply type filter
        $this->applyFilter($query, 'type', 'type');

        // Apply status filter
        if ($this->hasFilter('status')) {
            $isActive = $this->getFilter('status') === 'active';
            $query->where('is_active', $isActive);
        }

        return $query->orderBy('name', 'asc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Slug',
            'Type',
            'Description',
            'Status',
            'Residents Count',
            'Users Count',
            'Training Officers',
            'Created At',
        ];
    }

    public function map($institution): array
    {
        return [
            $institution->id,
            $institution->name,
            $institution->slug,
            $institution->type ? ucfirst($institution->type) : 'N/A',
            $institution->description ?? '',
            $institution->is_active ? 'Active' : 'Inactive',
            $institution->residents_count,
            $institution->users_count,
            $institution->training_officers_count,
            $institution->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
