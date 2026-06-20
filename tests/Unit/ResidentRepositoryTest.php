<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Resident;
use App\Repositories\Contracts\ResidentRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_gets_residents_for_a_specific_organization(): void
    {
        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $otherOrganization = Organization::create([
            'name' => 'Beta Chapter',
            'slug' => 'beta-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $resident = Resident::factory()->create([
            'organization_id' => $organization->id,
            'last_name' => 'Zulu',
            'first_name' => 'Anna',
        ]);

        Resident::factory()->create([
            'organization_id' => $otherOrganization->id,
        ]);

        $repository = app(ResidentRepositoryInterface::class);
        $residents = $repository->getForOrganization($organization->id);

        $this->assertCount(1, $residents);
        $this->assertTrue($residents->first()->is($resident));
    }

    public function test_it_finds_a_resident_only_within_the_given_organization(): void
    {
        $organization = Organization::create([
            'name' => 'Gamma Chapter',
            'slug' => 'gamma-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $otherOrganization = Organization::create([
            'name' => 'Delta Chapter',
            'slug' => 'delta-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $resident = Resident::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $otherResident = Resident::factory()->create([
            'organization_id' => $otherOrganization->id,
        ]);

        $repository = app(ResidentRepositoryInterface::class);

        $this->assertTrue(
            $repository->findForOrganization($organization->id, $resident->id)->is($resident)
        );

        $this->expectException(ModelNotFoundException::class);

        $repository->findForOrganization($organization->id, $otherResident->id);
    }
}
