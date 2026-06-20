<?php

namespace App\Repositories\Contracts;

use App\Models\Announcement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AnnouncementRepositoryInterface
{
    public function paginateVisibleToOrganization(int $organizationId, array $filters, int $perPage = 20): LengthAwarePaginator;

    public function paginateForManagement(int $organizationId, bool $canCreateSystem, array $filters, int $perPage = 20): LengthAwarePaginator;

    public function create(array $attributes): Announcement;

    public function update(Announcement $announcement, array $attributes): bool;

    public function delete(Announcement $announcement): bool;

    public function markAsViewedBy(Announcement $announcement, int $userId): void;
}
