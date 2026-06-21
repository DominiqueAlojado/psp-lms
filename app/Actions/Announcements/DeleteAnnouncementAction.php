<?php

namespace App\Actions\Announcements;

use App\Models\Announcement;
use App\Repositories\Contracts\AnnouncementRepositoryInterface;

class DeleteAnnouncementAction
{
    public function __construct(
        private readonly AnnouncementRepositoryInterface $announcementRepository,
    ) {}

    public function execute(Announcement $announcement): bool
    {
        return $this->announcementRepository->delete($announcement);
    }
}
