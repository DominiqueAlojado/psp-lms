<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Resident;
use App\Models\User;
use App\Services\ResidentReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentReadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_index_payload(): void
    {
        $service = app(ResidentReadService::class);

        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $user->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        Resident::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'first_name' => 'Jane',
            'middle_name' => 'Santos',
            'last_name' => 'Doe',
            'email' => 'resident@example.com',
            'year_level' => 'First Year',
            'status' => 'active',
        ]);

        $payload = $service->indexPayload([], $user);
        $resident = $payload['residents']->items()[0];

        $this->assertSame('Jane Santos Doe', $resident['full_name']);
        $this->assertSame('resident@example.com', $resident['email']);
        $this->assertSame('Alpha Chapter', $resident['organization']['name']);
        $this->assertStringContainsString('T', $resident['updated_at']);
        $this->assertNotEmpty($payload['organizations']);
    }

    public function test_it_builds_show_organizations_payload(): void
    {
        $service = app(ResidentReadService::class);

        $homeOrganization = Organization::create([
            'name' => 'Home Chapter',
            'slug' => 'home-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $otherOrganization = Organization::create([
            'name' => 'Other Chapter',
            'slug' => 'other-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $availableOrganization = Organization::create([
            'name' => 'Available Chapter',
            'slug' => 'available-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $homeOrganization->id,
        ]);
        $user->organizations()->attach($homeOrganization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);
        $user->organizations()->attach($otherOrganization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        $resident = Resident::factory()->create([
            'organization_id' => $homeOrganization->id,
            'user_id' => $user->id,
        ]);

        $payload = $service->showOrganizationsPayload($resident);

        $this->assertCount(2, $payload['currentOrganizations']);
        $this->assertTrue(collect($payload['availableOrganizations'])->contains('id', $availableOrganization->id));
        $this->assertFalse(collect($payload['availableOrganizations'])->contains('id', $homeOrganization->id));
    }

    public function test_it_limits_national_context_to_residents_bound_to_the_national_organization(): void
    {
        $service = app(ResidentReadService::class);

        $nationalOrganization = Organization::create([
            'name' => 'In-Service Exams',
            'slug' => 'in-service-exams',
            'type' => 'national',
            'is_active' => true,
        ]);

        $institutionA = Organization::create([
            'name' => 'Alpha Medical Center',
            'slug' => 'alpha-medical-center',
            'type' => 'institution',
            'is_active' => true,
        ]);

        $institutionB = Organization::create([
            'name' => 'Beta Medical Center',
            'slug' => 'beta-medical-center',
            'type' => 'institution',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $nationalOrganization->id,
        ]);
        $user->organizations()->attach($nationalOrganization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        $boundUser = User::factory()->create();
        $boundUser->organizations()->attach($nationalOrganization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        Resident::factory()->create([
            'organization_id' => $institutionA->id,
            'user_id' => $boundUser->id,
            'email' => 'resident-a@example.com',
            'first_name' => 'Alice',
            'middle_name' => null,
            'last_name' => 'Alpha',
        ]);

        $unboundUser = User::factory()->create();
        Resident::factory()->create([
            'organization_id' => $institutionB->id,
            'user_id' => $unboundUser->id,
            'email' => 'resident-b@example.com',
            'first_name' => 'Bob',
            'middle_name' => null,
            'last_name' => 'Beta',
        ]);

        $payload = $service->indexPayload([], $user);

        $this->assertFalse($payload['isAllOrganizationsContext']);
        $this->assertCount(1, $payload['residents']);
        $this->assertSame('Alice Alpha', $payload['residents']->items()[0]['full_name']);
        $this->assertCount(3, $payload['organizations']);
    }
}
