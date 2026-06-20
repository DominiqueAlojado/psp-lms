<?php

namespace App\Repositories\Contracts;

use App\Models\QuestionBank;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface QuestionBankRepositoryInterface
{
    public function paginateScoped(?int $organizationId, bool $isNational, array $filters, int $perPage = 20): LengthAwarePaginator;

    public function listScoped(?int $organizationId, bool $isNational, array $filters, int $limit = 100): Collection;

    public function create(array $attributes): QuestionBank;

    public function update(QuestionBank $question, array $attributes): bool;

    public function delete(QuestionBank $question): bool;

    public function assessmentsCount(QuestionBank $question): int;

    public function approve(QuestionBank $question, int $approvedBy): bool;

    public function getStatisticsData(?int $organizationId, bool $isNational): array;
}
