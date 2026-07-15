<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Topic;
use App\Services\TopicManagementService;
use App\Services\TopicReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopicServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_topic_read_service_lists_organization_and_global_topics(): void
    {
        $service = app(TopicReadService::class);

        $organization = Organization::create([
            'name' => 'Alpha Hospital',
            'slug' => 'alpha-hospital',
            'type' => 'institution',
            'is_active' => true,
        ]);
        $otherOrganization = Organization::create([
            'name' => 'Beta Hospital',
            'slug' => 'beta-hospital',
            'type' => 'institution',
            'is_active' => true,
        ]);

        Topic::create([
            'name' => 'Anatomy',
            'organization_id' => $organization->id,
            'is_global' => false,
        ]);
        Topic::create([
            'name' => 'Global Topic',
            'organization_id' => null,
            'is_global' => true,
        ]);
        Topic::create([
            'name' => 'Other Org Topic',
            'organization_id' => $otherOrganization->id,
            'is_global' => false,
        ]);

        $topics = $service->listForOrganization($organization->id);

        $this->assertCount(2, $topics);
        $this->assertSame(['Global Topic', 'Anatomy'], $topics->pluck('name')->all());
    }

    public function test_topic_management_service_creates_topic_for_organization(): void
    {
        $service = app(TopicManagementService::class);

        $organization = Organization::create([
            'name' => 'Alpha Hospital',
            'slug' => 'alpha-hospital',
            'type' => 'institution',
            'is_active' => true,
        ]);

        $topic = $service->createForOrganization($organization->id, [
            'name' => 'Pharmacology',
            'description' => 'Medication fundamentals',
        ]);

        $this->assertSame('Pharmacology', $topic->name);
        $this->assertSame($organization->id, $topic->organization_id);
        $this->assertFalse($topic->is_global);
        $this->assertDatabaseHas('topics', [
            'id' => $topic->id,
            'organization_id' => $organization->id,
            'name' => 'Pharmacology',
        ]);
    }
}
