<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Services\OrganizationReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationReadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_index_payload(): void
    {
        $service = app(OrganizationReadService::class);

        Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'description' => 'Primary chapter',
            'type' => 'chapter',
            'is_active' => true,
            'training_officers' => [10, 20],
        ]);

        $payload = $service->indexPayload([]);
        $institution = $payload['institutions']->items()[0];

        $this->assertSame('Alpha Chapter', $institution['name']);
        $this->assertSame('chapter', $institution['type']);
        $this->assertSame(2, $institution['training_officers_count']);
        $this->assertStringContainsString('T', $institution['updated_at']);
        $this->assertContains('chapter', $payload['types']->toArray());
        $this->assertSame(1, $payload['typeStats']['chapter']);
    }
}
