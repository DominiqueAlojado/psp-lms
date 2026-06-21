<?php

namespace App\Actions\Residents;

use App\Models\Resident;
use App\Repositories\Contracts\ResidentRepositoryInterface;

class DeleteResidentAction
{
    public function __construct(
        private readonly ResidentRepositoryInterface $residentRepository,
    ) {}

    public function execute(Resident $resident): bool
    {
        return $this->residentRepository->delete($resident);
    }
}
