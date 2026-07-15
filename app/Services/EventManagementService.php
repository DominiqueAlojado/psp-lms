<?php

namespace App\Services;

use App\Actions\Events\CreateEventAction;
use App\Actions\Events\DeleteEventAction;
use App\Actions\Events\UpdateEventAction;
use App\Models\Event;
use App\Models\User;
use App\Repositories\Contracts\EventRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class EventManagementService
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepository,
        private readonly CreateEventAction $createEventAction,
        private readonly UpdateEventAction $updateEventAction,
        private readonly DeleteEventAction $deleteEventAction,
    ) {}

    public function create(User $user, array $validated, ?UploadedFile $image = null): Event
    {
        return $this->createEventAction->execute([
            ...$validated,
            'image_path' => $this->storeImage($image),
            'organization_id' => $validated['scope'] === 'organization' ? $user->currentOrganization?->id : null,
            'created_by' => $user->id,
        ]);
    }

    public function update(User $user, Event $event, array $validated, ?UploadedFile $image = null): array
    {
        $validated['organization_id'] = ($validated['scope'] ?? $event->scope) === 'organization'
            ? $user->currentOrganization?->id
            : null;

        if ($image) {
            if ($event->image_path && Storage::disk('public')->exists($event->image_path)) {
                Storage::disk('public')->delete($event->image_path);
            }
            $validated['image_path'] = $this->storeImage($image);
        }

        $this->updateEventAction->execute($event, $validated);

        return $validated;
    }

    public function delete(Event $event): bool
    {
        return $this->deleteEventAction->execute($event);
    }

    public function hasConfirmedRegistrations(Event $event): bool
    {
        return $this->eventRepository->hasConfirmedRegistrations($event);
    }

    public function canCreateSystem(User $user): bool
    {
        try {
            return $user->hasPermissionTo('create-system-announcements')
                || $user->hasAnyRole(['System Admin', 'BOP']);
        } catch (PermissionDoesNotExist) {
            return $user->hasAnyRole(['System Admin', 'BOP']);
        }
    }

    private function storeImage(?UploadedFile $image): ?string
    {
        if (! $image) {
            return null;
        }

        $fileName = Str::uuid() . '.' . $image->getClientOriginalExtension();

        return $image->storeAs('event-posters', $fileName, 'public');
    }
}
