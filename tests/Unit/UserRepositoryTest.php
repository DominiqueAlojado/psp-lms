<?php

namespace Tests\Unit;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resets_email_verification_when_email_changes(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $repository = app(UserRepositoryInterface::class);

        $repository->updateProfile($user, [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $user->refresh();

        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('updated@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_it_keeps_email_verification_when_email_does_not_change(): void
    {
        $verifiedAt = now();
        $user = User::factory()->create([
            'email_verified_at' => $verifiedAt,
        ]);

        $repository = app(UserRepositoryInterface::class);

        $repository->updateProfile($user, [
            'name' => 'Updated Name',
            'email' => $user->email,
        ]);

        $this->assertSame(
            $verifiedAt->toDateTimeString(),
            $user->refresh()->email_verified_at->toDateTimeString()
        );
    }

    public function test_it_updates_password_using_hashed_casting(): void
    {
        $user = User::factory()->create();

        $repository = app(UserRepositoryInterface::class);

        $repository->updatePassword($user, 'new-password');

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }
}
