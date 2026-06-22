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
            'organization_id' => $user->currentOrganization?->id,
            'created_by' => $user->id,
        ]);
    }

    public function update(Event $event, array $validated, ?UploadedFile $image = null): array
    {
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

    private function storeImage(?UploadedFile $image): ?string
    {
        if (! $image) {
            return null;
        }

        $fileName = Str::uuid() . '.' . $image->getClientOriginalExtension();

        return $image->storeAs('event-posters', $fileName, 'public');
    }
}
