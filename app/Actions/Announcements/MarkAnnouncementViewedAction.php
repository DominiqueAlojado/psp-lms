<?php

namespace App\Actions\Announcements;

use App\Models\Announcement;
use App\Repositories\Contracts\AnnouncementRepositoryInterface;

class MarkAnnouncementViewedAction
{
    public function __construct(
        private readonly AnnouncementRepositoryInterface $announcementRepository,
    ) {}

    public function execute(Announcement $announcement, int $userId): void
    {
        $this->announcementRepository->markAsViewedBy($announcement, $userId);
    }
}
