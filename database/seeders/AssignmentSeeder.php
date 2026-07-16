<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Organization;
use App\Models\Resident;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class AssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()
            ->where('slug', 'bataan-general-hospital')
            ->first() ?? Organization::query()->first();

        if (! $organization) {
            $this->command->warn('No organization found. Skipping assignment seeding.');

            return;
        }

        $creator = $organization->users()
            ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'Resident'))
            ->first() ?? User::query()->first();

        if (! $creator) {
            $this->command->warn('No assignment creator available. Skipping assignment seeding.');

            return;
        }

        $residents = Resident::query()
            ->with('user')
            ->where('organization_id', $organization->id)
            ->whereNotNull('user_id')
            ->take(4)
            ->get();

        $assignments = [
            [
                'match' => ['organization_id' => $organization->id, 'title' => 'Monthly Histopathology Case Reflection'],
                'attributes' => [
                    'description' => 'Submit a concise reflection on one histopathology case encountered this month.',
                    'instructions' => 'Include the case summary, differential diagnosis, final interpretation, and a short reflection on what changed your thinking.',
                    'assignment_type' => 'reflection',
                    'target_year_levels' => ['First Year', 'Second Year'],
                    'course_id' => null,
                    'max_score' => 20,
                    'due_date' => now()->addDays(7)->setTime(23, 59),
                    'allow_late_submission' => true,
                    'late_submission_until' => now()->addDays(10)->setTime(23, 59),
                    'late_penalty_percent' => 10,
                    'allow_resubmission' => true,
                    'max_submissions' => 2,
                    'allowed_file_types' => ['pdf', 'docx'],
                    'max_file_size_mb' => 10,
                    'max_files' => 2,
                    'is_published' => true,
                    'created_by' => $creator->id,
                ],
            ],
            [
                'match' => ['organization_id' => $organization->id, 'title' => 'Tumor Board Slide Review Presentation'],
                'attributes' => [
                    'description' => 'Prepare a short slide review for the upcoming multidisciplinary tumor board.',
                    'instructions' => 'Upload your slide deck and attach a one-page summary of the pathology findings and clinical significance.',
                    'assignment_type' => 'presentation',
                    'target_year_levels' => ['Third Year', 'Fourth Year', 'Graduate'],
                    'course_id' => null,
                    'max_score' => 50,
                    'due_date' => now()->addDays(14)->setTime(17, 0),
                    'allow_late_submission' => false,
                    'late_submission_until' => null,
                    'late_penalty_percent' => 0,
                    'allow_resubmission' => false,
                    'max_submissions' => 1,
                    'allowed_file_types' => ['pdf', 'ppt', 'pptx'],
                    'max_file_size_mb' => 25,
                    'max_files' => 3,
                    'is_published' => true,
                    'created_by' => $creator->id,
                ],
            ],
        ];

        foreach ($assignments as $payload) {
            $assignment = Assignment::query()->updateOrCreate($payload['match'], $payload['attributes']);

            $this->seedSubmissions($assignment, $organization, $creator, $residents);
        }

        $this->command->info('Seeded sample assignments and submissions.');
    }

    private function seedSubmissions(Assignment $assignment, Organization $organization, User $creator, Collection $residents): void
    {
        foreach ($residents->values()->take(2) as $index => $resident) {
            if (! $resident->user) {
                continue;
            }

            $isGraded = $index === 0;
            $submittedAt = now()->subDays(2 - $index);

            Submission::query()->updateOrCreate(
                [
                    'assignment_id' => $assignment->id,
                    'user_id' => $resident->user_id,
                    'submission_number' => 1,
                ],
                [
                    'organization_id' => $organization->id,
                    'year_level' => $resident->year_level,
                    'submission_text' => sprintf(
                        'Seeded submission for %s. Focused on %s and resident-level reflection.',
                        $assignment->title,
                        strtolower($assignment->assignment_type)
                    ),
                    'submitted_at' => $submittedAt,
                    'score' => $isGraded ? round($assignment->max_score * (0.8 + ($index * 0.1)), 2) : null,
                    'max_score' => $assignment->max_score,
                    'status' => $isGraded ? 'graded' : 'submitted',
                    'grader_feedback' => $isGraded ? 'Strong submission. Tighten the discussion around diagnostic pitfalls.' : null,
                    'graded_by' => $isGraded ? $creator->id : null,
                    'graded_at' => $isGraded ? $submittedAt->copy()->addDay() : null,
                    'is_late' => false,
                    'late_days' => 0,
                ]
            );
        }
    }
}
