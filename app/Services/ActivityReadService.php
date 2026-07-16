<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\Event;
use App\Models\Institution\InstitutionAssessment;
use App\Models\LearningResource;
use App\Models\National\NationalAssessment;
use App\Models\Organization;
use App\Models\QuestionBank;
use App\Models\Resident;
use App\Models\SupportTicket;
use App\Models\User;
use App\Repositories\Contracts\ActivityRepositoryInterface;

class ActivityReadService
{
    private const ALL_ORGANIZATIONS_SLUG = 'all-organizations';

    public function __construct(
        private readonly ActivityRepositoryInterface $activityRepository,
    ) {}

    public function indexPayload(User $user, array $filters): array
    {
        $organization = $user->currentOrganization;
        $isAllOrganizationsContext = data_get($organization, 'slug') === self::ALL_ORGANIZATIONS_SLUG;

        if ($isAllOrganizationsContext) {
            $organizationIds = $user->organizations()
                ->wherePivot('organization_user.is_active', true)
                ->pluck('organizations.id')
                ->all();

            abort_if($organizationIds === [], 403, 'No accessible organizations found.');

            return [
                'activities' => $this->activityRepository
                    ->paginateForOrganizations($organizationIds, $user->hasAnyRole(['System Admin', 'BOP']), $filters)
                    ->through(fn ($activity) => [
                        'id' => $activity->id,
                        'description' => $activity->description,
                        'module' => $activity->log_name,
                        'module_label' => $this->moduleLabel($activity->log_name),
                        'action' => $this->detectAction($activity->description),
                        'subject_label' => $this->subjectLabel($activity->subject, $activity->properties['attributes'] ?? []),
                        'subject_type' => $activity->subject_type ? class_basename($activity->subject_type) : null,
                        'organization_name' => $this->organizationName($activity->subject),
                        'actor' => $activity->causer ? [
                            'name' => $activity->causer->name,
                            'email' => $activity->causer->email,
                        ] : null,
                        'changes' => $this->formatChanges($activity->properties['attributes'] ?? [], $activity->properties['old'] ?? []),
                        'created_at' => $activity->created_at->toIso8601String(),
                        'created_at_human' => $activity->created_at->diffForHumans(),
                    ]),
                'summary' => $this->activityRepository->getSummaryForOrganizations(
                    $organizationIds,
                    $user->hasAnyRole(['System Admin', 'BOP']),
                    $filters
                ),
                'filters' => $filters,
                'modules' => $this->modules(),
            ];
        }

        $organizationId = $organization?->id;

        abort_if($organizationId === null, 403, 'No active organization selected.');

        $activities = $this->activityRepository->paginateForOrganization(
            $organizationId,
            $organization?->type,
            $filters
        );

        return [
            'activities' => $activities->through(fn ($activity) => [
                'id' => $activity->id,
                'description' => $activity->description,
                'module' => $activity->log_name,
                'module_label' => $this->moduleLabel($activity->log_name),
                'action' => $this->detectAction($activity->description),
                'subject_label' => $this->subjectLabel($activity->subject, $activity->properties['attributes'] ?? []),
                'subject_type' => $activity->subject_type ? class_basename($activity->subject_type) : null,
                'organization_name' => $this->organizationName($activity->subject),
                'actor' => $activity->causer ? [
                    'name' => $activity->causer->name,
                    'email' => $activity->causer->email,
                ] : null,
                'changes' => $this->formatChanges($activity->properties['attributes'] ?? [], $activity->properties['old'] ?? []),
                'created_at' => $activity->created_at->toIso8601String(),
                'created_at_human' => $activity->created_at->diffForHumans(),
            ]),
            'summary' => $this->activityRepository->getSummaryForOrganization(
                $organizationId,
                $organization?->type,
                $filters
            ),
            'filters' => $filters,
            'modules' => $this->modules(),
        ];
    }

    private function modules(): array
    {
        return [
            ['value' => 'announcements', 'label' => 'Announcements'],
            ['value' => 'assignments', 'label' => 'Assignments'],
            ['value' => 'events', 'label' => 'Events'],
            ['value' => 'national_assessment', 'label' => 'In-Service Exams'],
            ['value' => 'organizations', 'label' => 'Institutions'],
            ['value' => 'question_bank', 'label' => 'Question Bank'],
            ['value' => 'residents', 'label' => 'Residents'],
            ['value' => 'resources', 'label' => 'Learning Resources'],
            ['value' => 'support', 'label' => 'Support'],
            ['value' => 'users', 'label' => 'Staff'],
        ];
    }

    private function moduleLabel(?string $module): string
    {
        return collect($this->modules())
            ->firstWhere('value', $module)['label']
            ?? str($module ?? 'activity')->replace('_', ' ')->title()->toString();
    }

    private function detectAction(string $description): string
    {
        $normalized = str($description)->lower()->toString();

        return match (true) {
            str_contains($normalized, 'created') => 'created',
            str_contains($normalized, 'updated') => 'updated',
            str_contains($normalized, 'deleted') => 'deleted',
            str_contains($normalized, 'import') => 'imported',
            str_contains($normalized, 'approve') => 'approved',
            str_contains($normalized, 'duplicate') => 'duplicated',
            str_contains($normalized, 'transfer') => 'transferred',
            default => 'activity',
        };
    }

    private function subjectLabel(mixed $subject, array $attributes): string
    {
        if ($subject instanceof Announcement
            || $subject instanceof Assignment
            || $subject instanceof Event
            || $subject instanceof InstitutionAssessment
            || $subject instanceof LearningResource
            || $subject instanceof NationalAssessment
            || $subject instanceof Organization
            || $subject instanceof SupportTicket
        ) {
            return $subject->title ?? $subject->name ?? ($attributes['title'] ?? $attributes['name'] ?? 'Record');
        }

        if ($subject instanceof QuestionBank) {
            return str($subject->question_text)->limit(80)->toString();
        }

        if ($subject instanceof Resident) {
            return $subject->full_name ?? $attributes['full_name'] ?? $attributes['email'] ?? 'Resident';
        }

        if ($subject instanceof User) {
            return $subject->name;
        }

        return $attributes['title']
            ?? $attributes['name']
            ?? $attributes['question_text']
            ?? 'Record';
    }

    private function organizationName(mixed $subject): ?string
    {
        return match (true) {
            $subject instanceof Announcement => $subject->scope === 'system' ? 'All Organizations' : $subject->organization?->name,
            $subject instanceof Event => $subject->scope === 'system' ? 'All Organizations' : $subject->organization?->name,
            $subject instanceof LearningResource => $subject->scope === 'system' ? 'All Organizations' : $subject->organization?->name,
            $subject instanceof Assignment,
            $subject instanceof InstitutionAssessment,
            $subject instanceof QuestionBank,
            $subject instanceof Resident,
            $subject instanceof SupportTicket => $subject->organization?->name,
            $subject instanceof Organization => $subject->name,
            $subject instanceof User => $subject->currentOrganization?->name,
            $subject instanceof NationalAssessment => 'National',
            default => null,
        };
    }

    private function formatChanges(array $attributes, array $oldValues): array
    {
        $changes = [];

        foreach ($attributes as $key => $value) {
            $oldValue = $oldValues[$key] ?? null;

            if ($oldValue === $value) {
                continue;
            }

            $changes[] = [
                'field' => str($key)->replace('_', ' ')->title()->toString(),
                'old' => $this->formatValue($oldValue),
                'new' => $this->formatValue($value),
            ];
        }

        return $changes;
    }

    private function formatValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'None';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return empty($value) ? 'None' : collect($value)->map(function ($item) {
                if (is_array($item)) {
                    return $item['name'] ?? json_encode($item);
                }

                return (string) $item;
            })->join(', ');
        }

        return (string) $value;
    }
}
