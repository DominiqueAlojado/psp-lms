<?php

namespace Database\Seeders;

use App\Models\FeedbackEntry;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class FeedbackSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()
            ->where('slug', 'bataan-general-hospital')
            ->first() ?? Organization::query()->first();

        if (! $organization) {
            $this->command->warn('No organization found. Skipping feedback seeding.');

            return;
        }

        $users = $organization->users()
            ->orderBy('users.id')
            ->take(3)
            ->get();

        if ($users->isEmpty()) {
            $users = User::query()->take(3)->get();
        }

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Skipping feedback seeding.');

            return;
        }

        $entries = [
            [
                'match' => [
                    'organization_id' => $organization->id,
                    'user_id' => $users->get(0)?->id,
                    'module_name' => 'Assessment Reports',
                    'page_url' => '/assessment-reports/by-resident',
                ],
                'attributes' => [
                    'overall_rating' => 4,
                    'content_rating' => 4,
                    'support_rating' => 3,
                    'usability_rating' => 4,
                    'context' => 'Resident dashboard workflow',
                    'comment' => 'Assessment reports are useful, but the result breakdown could be easier to scan on smaller screens.',
                    'would_recommend' => true,
                ],
            ],
            [
                'match' => [
                    'organization_id' => $organization->id,
                    'user_id' => $users->get(1)?->id ?? $users->first()->id,
                    'module_name' => 'Assignments',
                    'page_url' => '/assignments',
                ],
                'attributes' => [
                    'overall_rating' => 3,
                    'content_rating' => 3,
                    'support_rating' => 3,
                    'usability_rating' => 2,
                    'context' => 'Assignment submission',
                    'comment' => 'The assignment area works, but residents would benefit from clearer status messaging after upload and grading.',
                    'would_recommend' => true,
                ],
            ],
            [
                'match' => [
                    'organization_id' => $organization->id,
                    'user_id' => $users->get(2)?->id ?? $users->first()->id,
                    'module_name' => 'Support',
                    'page_url' => '/support',
                ],
                'attributes' => [
                    'overall_rating' => 4,
                    'content_rating' => 4,
                    'support_rating' => 4,
                    'usability_rating' => 3,
                    'context' => 'Support request flow',
                    'comment' => 'The support form is straightforward. A clearer confirmation after submission would make the flow feel more complete.',
                    'would_recommend' => true,
                ],
            ],
        ];

        foreach ($entries as $entry) {
            FeedbackEntry::query()->updateOrCreate(
                $entry['match'],
                $entry['attributes']
            );
        }

        $this->command->info('Seeded sample feedback entries.');
    }
}
