<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

abstract class BaseExport implements FromQuery, WithHeadings, WithMapping
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Return the base query for the export.
     */
    abstract public function query(): Builder;

    /**
     * Return the column headings for the export.
     */
    abstract public function headings(): array;

    /**
     * Map each row for the export.
     */
    abstract public function map($row): array;

    /**
     * Get the value of a filter or return default.
     */
    protected function getFilter(string $key, mixed $default = null): mixed
    {
        return $this->filters[$key] ?? $default;
    }

    /**
     * Check if a filter exists and is not empty.
     */
    protected function hasFilter(string $key): bool
    {
        return ! empty($this->filters[$key]);
    }

    /**
     * Apply a filter to the query if it exists.
     */
    protected function applyFilter(Builder $query, string $key, string $column, string $operator = '='): Builder
    {
        if ($this->hasFilter($key)) {
            $query->where($column, $operator, $this->filters[$key]);
        }

        return $query;
    }

    /**
     * Apply a search filter if it exists (assuming model has search scope).
     */
    protected function applySearch(Builder $query, string $key = 'search'): Builder
    {
        if ($this->hasFilter($key) && method_exists($query->getModel(), 'scopeSearch')) {
            $query->search($this->filters[$key]);
        }

        return $query;
    }
}
