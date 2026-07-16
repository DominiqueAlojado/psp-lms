<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

class NotificationReadService
{
    public function sharedPayload(User $user, int $limit = 5): array
    {
        return [
            'unreadCount' => $user->unreadNotifications()->count(),
            'latest' => $user->notifications()
                ->latest()
                ->limit($limit)
                ->get()
                ->map(fn (DatabaseNotification $notification) => $this->format($notification))
                ->all(),
        ];
    }

    public function indexPayload(User $user): array
    {
        return [
            'summary' => [
                'total' => $user->notifications()->count(),
                'unread' => $user->unreadNotifications()->count(),
                'read' => $user->readNotifications()->count(),
            ],
            'notificationFeed' => $user->notifications()
                ->latest()
                ->paginate(20)
                ->through(fn (DatabaseNotification $notification) => $this->format($notification)),
        ];
    }

    public function markAsRead(User $user, string $notificationId): void
    {
        $notification = $this->findOwnedNotification($user, $notificationId);
        abort_if($notification === null, 404);

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }
    }

    public function markAllAsRead(User $user): void
    {
        $user->unreadNotifications->markAsRead();
    }

    public function findOwnedNotification(User $user, string $notificationId): ?DatabaseNotification
    {
        return $user->notifications()->whereKey($notificationId)->first();
    }

    private function format(DatabaseNotification $notification): array
    {
        $data = $notification->data;

        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $data['title'] ?? 'Notification',
            'message' => $data['message'] ?? '',
            'category' => $data['category'] ?? 'general',
            'event' => $data['event'] ?? 'general',
            'url' => $data['url'] ?? '/dashboard',
            'ticket_number' => $data['ticket_number'] ?? null,
            'organization_name' => $data['organization_name'] ?? null,
            'actor_name' => $data['actor_name'] ?? null,
            'is_read' => $notification->read_at !== null,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at->format('M d, Y h:i A'),
            'created_at_human' => $notification->created_at->diffForHumans(),
        ];
    }
}
