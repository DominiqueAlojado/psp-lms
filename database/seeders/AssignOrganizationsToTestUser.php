<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssignOrganizationsToTestUser extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->first();

        if (! $user) {
            $this->command->warn('Test user not found');

            return;
        }

        // Get 5 organizations
        $organizations = Organization::take(5)->get();

        // Attach organizations to user
        foreach ($organizations as $organization) {
            $user->organizations()->syncWithoutDetaching([
                $organization->id => [
                    'joined_at' => now(),
                    'is_active' => true,
                ],
            ]);
        }

        // Set current organization
        $user->update(['current_organization_id' => $organizations->first()->id]);

        $this->command->info('Assigned '.count($organizations).' organizations to test user:');
        $this->command->table(
            ['ID', 'Name', 'Type'],
            $organizations->map(fn ($org) => [$org->id, $org->name, $org->type])->toArray()
        );
        $this->command->info('Current organization: '.$organizations->first()->name);
    }
}
