<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class SupportTicketSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()
            ->where('slug', 'bataan-general-hospital')
            ->first() ?? Organization::query()->first();

        if (! $organization) {
            $this->command->warn('No organization found. Skipping support ticket seeding.');

            return;
        }

        $requester = $organization->users()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Resident'))
            ->first() ?? $organization->users()->first();

        $assignee = $organization->users()
            ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'Resident'))
            ->first() ?? User::query()->first();

        if (! $requester || ! $assignee) {
            $this->command->warn('Not enough users found to seed support tickets.');

            return;
        }

        $tickets = [
            [
                'ticket_number' => 'SUP-DEMO-0001',
                'title' => 'Unable to open exam result modal on Safari',
                'category' => 'bug',
                'priority' => 'high',
                'status' => 'in_review',
                'module_name' => 'Assessment Reports',
                'page_url' => '/assessment-reports/by-resident',
                'details' => 'The resident can log in, but the result modal fails to open consistently on Safari after exam completion.',
                'assigned_to_user_id' => $assignee->id,
                'resolved_at' => null,
                'last_replied_at' => now()->subHours(6),
                'messages' => [
                    [$requester->id, 'I can access the exam history, but clicking View Result does nothing on Safari.'],
                    [$assignee->id, 'We have reproduced this on Safari 17 and are checking the modal fetch flow.'],
                ],
            ],
            [
                'ticket_number' => 'SUP-DEMO-0002',
                'title' => 'Need clearer instructions for assignment resubmission',
                'category' => 'feature',
                'priority' => 'medium',
                'status' => 'open',
                'module_name' => 'Assignments',
                'page_url' => '/assignments/my-assignments',
                'details' => 'Residents are confused about when a second submission is allowed and whether the previous file stays attached.',
                'assigned_to_user_id' => null,
                'resolved_at' => null,
                'last_replied_at' => now()->subDay(),
                'messages' => [
                    [$requester->id, 'A short helper message in the submission panel would probably reduce repeated questions.'],
                ],
            ],
            [
                'ticket_number' => 'SUP-DEMO-0003',
                'title' => 'Event registration confirmation email not received',
                'category' => 'account',
                'priority' => 'low',
                'status' => 'resolved',
                'module_name' => 'Events',
                'page_url' => '/events',
                'details' => 'Registration succeeded in the portal, but the resident did not receive a confirmation email for the CME workshop.',
                'assigned_to_user_id' => $assignee->id,
                'resolved_at' => now()->subHours(12),
                'last_replied_at' => now()->subHours(12),
                'messages' => [
                    [$requester->id, 'The registration appears in My Registrations, but no confirmation email arrived.'],
                    [$assignee->id, 'We confirmed the registration was successful. This was a mail delivery issue and has been resolved.'],
                ],
            ],
        ];

        foreach ($tickets as $payload) {
            $ticket = SupportTicket::query()->updateOrCreate(
                ['ticket_number' => $payload['ticket_number']],
                [
                    'organization_id' => $organization->id,
                    'user_id' => $requester->id,
                    'assigned_to_user_id' => $payload['assigned_to_user_id'],
                    'title' => $payload['title'],
                    'category' => $payload['category'],
                    'priority' => $payload['priority'],
                    'status' => $payload['status'],
                    'module_name' => $payload['module_name'],
                    'page_url' => $payload['page_url'],
                    'details' => $payload['details'],
                    'resolved_at' => $payload['resolved_at'],
                    'last_replied_at' => $payload['last_replied_at'],
                ]
            );

            $this->syncMessages($ticket, collect($payload['messages']));
        }

        $this->command->info('Seeded sample support tickets and replies.');
    }

    private function syncMessages(SupportTicket $ticket, Collection $messages): void
    {
        SupportTicketMessage::query()
            ->where('support_ticket_id', $ticket->id)
            ->delete();

        foreach ($messages as [$userId, $message]) {
            SupportTicketMessage::query()->create([
                'support_ticket_id' => $ticket->id,
                'user_id' => $userId,
                'message' => $message,
            ]);
        }
    }
}
