<?php

namespace App\Exports;

use App\Models\Resident;
use Illuminate\Database\Eloquent\Builder;

class ResidentsExport extends BaseExport
{
    public function query(): Builder
    {
        $query = Resident::query()->with(['organization']);

        // Apply search filter
        $this->applySearch($query);

        // Apply other filters
        $this->applyFilter($query, 'organization_id', 'organization_id');
        $this->applyFilter($query, 'year_level', 'year_level');
        $this->applyFilter($query, 'status', 'status');
        $this->applyFilter($query, 'course', 'course');

        return $query->orderBy('last_name', 'asc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Organization',
            'First Name',
            'Middle Name',
            'Last Name',
            'Email',
            'Contact Number',
            'Course',
            'Year Level',
            'Status',
            'Created At',
        ];
    }

    public function map($resident): array
    {
        return [
            $resident->id,
            $resident->organization->name ?? 'N/A',
            $resident->first_name,
            $resident->middle_name,
            $resident->last_name,
            $resident->email,
            $resident->contact_number,
            $resident->course,
            $resident->year_level,
            ucfirst($resident->status),
            $resident->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
