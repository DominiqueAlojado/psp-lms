<?php

namespace Tests\Feature;

use App\Http\Middleware\SetOrganizationFromUrl;
use App\Models\LearningResource;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ResourceManagementFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_learning_resource(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        Storage::fake('public');

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

        $file = UploadedFile::fake()->create('guide.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)
            ->from(route('resources.manage'))
            ->post(route('resources.store'), [
                'title' => 'Study Guide',
                'description' => 'Resource description',
                'category' => 'Guides',
                'target_year_levels' => ['First Year'],
                'is_published' => true,
                'file' => $file,
            ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect('/resources/manage');

        $resource = LearningResource::where('title', 'Study Guide')->first();

        $this->assertNotNull($resource);
        $this->assertSame($organization->id, $resource->organization_id);
        $this->assertSame($user->id, $resource->uploaded_by);
        $this->assertSame('Guides', $resource->category);
        $this->assertSame(['First Year'], $resource->target_year_levels);
        $this->assertTrue($resource->is_published);
        Storage::disk('public')->assertExists($resource->file_path);
    }

    public function test_authorized_user_can_update_learning_resource(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        Permission::create(['name' => 'edit-materials', 'guard_name' => 'web']);

        $organization = Organization::create([
            'name' => 'Beta Chapter',
            'slug' => 'beta-chapter',
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
        $user->givePermissionTo('edit-materials');

        $resource = LearningResource::create([
            'organization_id' => $organization->id,
            'uploaded_by' => $user->id,
            'title' => 'Old Title',
            'description' => 'Old description',
            'category' => 'Handouts',
            'file_path' => 'resources/old.pdf',
            'file_name' => 'old.pdf',
            'file_type' => 'pdf',
            'file_size' => 1024,
            'target_year_levels' => ['First Year'],
            'is_published' => true,
            'download_count' => 0,
        ]);

        $response = $this->actingAs($user)
            ->from(route('resources.manage'))
            ->patch(route('resources.update', $resource), [
                'title' => 'Updated Title',
                'description' => 'Updated description',
                'category' => 'Videos',
                'target_year_levels' => ['Second Year'],
                'is_published' => false,
            ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('resources.manage'));

        $resource->refresh();

        $this->assertSame('Updated Title', $resource->title);
        $this->assertSame('Updated description', $resource->description);
        $this->assertSame('Videos', $resource->category);
        $this->assertSame(['Second Year'], $resource->target_year_levels);
        $this->assertFalse($resource->is_published);
    }
}
