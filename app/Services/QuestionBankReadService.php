<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\QuestionBankRepositoryInterface;
use Illuminate\Support\Collection;

class QuestionBankReadService
{
    public function __construct(
        private readonly QuestionBankRepositoryInterface $questionBankRepository,
    ) {}

    public function indexPayload(User $user, array $filters): array
    {
        $context = $this->resolveScopeContext($user);

        return [
            'questions' => $this->questionBankRepository->paginateScoped(
                $context['organizationId'],
                $context['isNational'],
                $filters
            ),
        ];
    }

    public function listPayload(User $user, array $filters, string $scope = ''): array
    {
        $context = $this->resolveScopeContext($user, $scope);

        return [
            'data' => $this->questionBankRepository->listScoped(
                $context['organizationId'],
                $context['isNational'],
                $filters
            ),
        ];
    }

    public function statisticsPayload(User $user): array
    {
        $context = $this->resolveScopeContext($user);

        return $this->questionBankRepository->getStatisticsData(
            $context['organizationId'],
            $context['isNational']
        );
    }

    public function resolveScopeContext(User $user, string $scope = ''): array
    {
        $currentOrganization = $user->currentOrganization;
        $organizationId = $currentOrganization?->id;

        if ($scope === 'national') {
            $isNational = true;
        } elseif ($scope === 'institution') {
            $isNational = false;
        } else {
            $isNational = $currentOrganization?->type === 'national';
        }

        return [
            'organizationId' => $organizationId,
            'isNational' => $isNational,
            'scope' => $isNational ? 'national' : 'institution',
        ];
    }
}
