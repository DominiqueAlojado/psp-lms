<?php

namespace App\Actions\Announcements;

use App\Models\Announcement;
use App\Repositories\Contracts\AnnouncementRepositoryInterface;

class UpdateAnnouncementAction
{
    public function __construct(
        private readonly AnnouncementRepositoryInterface $announcementRepository,
    ) {}

    public function execute(Announcement $announcement, array $attributes): bool
    {
        return $this->announcementRepository->update($announcement, $attributes);
    }
}
