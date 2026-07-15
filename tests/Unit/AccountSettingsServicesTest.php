<?php

namespace Tests\Unit;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\PasswordSettingsManagementService;
use App\Services\ProfileSettingsManagementService;
use Mockery;
use Tests\TestCase;

class AccountSettingsServicesTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_profile_management_service_updates_profile_via_repository(): void
    {
        $repository = Mockery::mock(UserRepositoryInterface::class);
        $user = Mockery::mock(User::class)->makePartial();

        $repository->shouldReceive('updateProfile')
            ->once()
            ->with($user, ['name' => 'Updated User']);

        $service = new ProfileSettingsManagementService($repository);
        $service->updateProfile($user, ['name' => 'Updated User']);

        $this->assertTrue(true);
    }

    public function test_password_management_service_updates_password_via_repository(): void
    {
        $repository = Mockery::mock(UserRepositoryInterface::class);
        $user = Mockery::mock(User::class)->makePartial();

        $repository->shouldReceive('updatePassword')
            ->once()
            ->with($user, 'secret-123');

        $service = new PasswordSettingsManagementService($repository);
        $service->updatePassword($user, 'secret-123');

        $this->assertTrue(true);
    }
}
