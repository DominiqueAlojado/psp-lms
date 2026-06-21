<?php

namespace App\Actions\Announcements;

use App\Models\Announcement;
use App\Repositories\Contracts\AnnouncementRepositoryInterface;

class CreateAnnouncementAction
{
    public function __construct(
        private readonly AnnouncementRepositoryInterface $announcementRepository,
    ) {}

    public function execute(array $attributes): Announcement
    {
        return $this->announcementRepository->create($attributes);
    }
}
