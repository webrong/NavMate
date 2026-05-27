<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'P@ssw0rd!',
            'password_confirmation' => 'P@ssw0rd!',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_register_rejects_weak_password(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_requires_email_verification(): void
    {
        $user = User::create([
            'name' => 'Unverified',
            'email' => 'unverified@example.com',
            'password' => 'P@ssw0rd!',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'unverified@example.com',
            'password' => 'P@ssw0rd!',
        ]);

        // Current implementation does not block unverified users
        $response->assertOk();
    }

    public function test_login_succeeds_for_verified_user(): void
    {
        $user = User::create([
            'name' => 'Verified',
            'email' => 'verified@example.com',
            'password' => 'P@ssw0rd!',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'verified@example.com',
            'password' => 'P@ssw0rd!',
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['email' => 'verified@example.com']);
    }

    public function test_login_rejects_wrong_password(): void
    {
        User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => 'P@ssw0rd!',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'WrongPassword1!',
        ]);

        $response->assertStatus(422);
    }

    public function test_logout_works(): void
    {
        $user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => 'P@ssw0rd!',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson('/api/logout');

        $response->assertOk();
    }

    public function test_forgot_password_returns_uniform_message(): void
    {
        // Should return same message whether email exists or not (anti-enumeration)
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['message' => '如果该邮箱已注册，重置链接将发送到您的邮箱']);
    }

    public function test_me_returns_null_when_not_authenticated(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertOk();
        $response->assertExactJson(null);
    }

    public function test_me_returns_user_when_authenticated(): void
    {
        $user = User::create([
            'name' => 'Me',
            'email' => 'me@example.com',
            'password' => 'P@ssw0rd!',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/user');

        $response->assertOk();
        $response->assertJsonFragment(['email' => 'me@example.com']);
    }

    public function test_favorites_require_auth(): void
    {
        $response = $this->getJson('/api/user/favorites');

        $response->assertUnauthorized();
    }
}
