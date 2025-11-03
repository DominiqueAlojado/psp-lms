<?php

namespace App\Exports;

use App\Models\Resident;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ResidentsExport implements FromQuery, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = Resident::query()
            ->with(['organization']);

        // Apply filters if provided
        if (! empty($this->filters['search'])) {
            $query->search($this->filters['search']);
        }

        if (! empty($this->filters['organization_id'])) {
            $query->where('organization_id', $this->filters['organization_id']);
        }

        if (! empty($this->filters['year_level'])) {
            $query->where('year_level', $this->filters['year_level']);
        }

        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (! empty($this->filters['course'])) {
            $query->where('course', $this->filters['course']);
        }

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
